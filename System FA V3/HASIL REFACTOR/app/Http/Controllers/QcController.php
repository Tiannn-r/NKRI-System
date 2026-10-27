<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Assembly;
use App\Models\Tray;
use App\Models\MasterStock;
use App\Models\PartAuditLog;

class QcController extends Controller
{
    /**
     * Mengambil daftar seluruh unit yang masuk ke antrean Quality Control (TF 2)
     */
    public function items(Request $request)
    {
        $status = $request->input('status'); // 'pending', 'history', 'all'
        $trayCode = $request->input('tray_code');

        $query = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->select([
                'a.id',
                'a.tray_id',
                't.tray_code',
                'a.set_number',
                'a.pic_assembly',
                'a.part15_sn',
                'a.part12_sn',
                'a.part1_sn',
                'a.status as assembly_status',
                'a.assy_test_status',
                'a.reason',
                'a.rework_reason',
                'a.assy_test_checklist', // Rekap hasil 17 checklist TF 1 untuk ditampilkan Read-Only di TF 2
                'a.notes as assy_notes',
                'a.assy_tester_username',
                'a.assy_tested_at',
                'a.assy_doc_number',
                'a.qc_status',
                'a.qc_failure_reasons',
                'a.qc_inspector_username',
                'a.qc_tested_at',
                'a.qc_doc_number',
                'a.started_at',
                'a.finished_at',
            ]);

        // Yang berhak masuk ke TF 2 adalah barang yang Good di perakitan
        $query->where('a.status', 'Good');

        if ($trayCode) {
            $query->where('t.tray_code', $trayCode);
        }

        $items = $query->orderBy('a.id', 'desc')->get()->map(function ($item) {
            $item->assy_test_checklist = json_decode($item->assy_test_checklist ?: '[]');
            return $item;
        });

        $pendingItems = $items->filter(function ($i) {
            $st = strtoupper($i->qc_status ?? '');
            return ($st === 'PENDING_QC' || $st === 'PENDING' || empty($st)) 
                && $i->assembly_status === 'Good' 
                && strtoupper($i->assy_test_status ?? '') === 'GOOD'
                && !empty($i->assy_doc_number);
        })->values();

        $historyItems = $items->filter(function ($i) {
            $st = strtoupper($i->qc_status ?? '');
            return in_array($st, ['GOOD', 'HOLD_QC', 'REWORK_QC', 'HOLD', 'RU']) || !empty($i->qc_doc_number) || !empty($i->qc_tested_at);
        })->values();

        $responseData = $items;
        if ($status === 'pending') {
            $responseData = $pendingItems;
        } elseif ($status === 'history') {
            $responseData = $historyItems;
        } else {
            // Default untuk pending-items: data = pending, history_data = history, all_data = all
            $responseData = $pendingItems;
        }

        return response()->json([
            'ok' => true,
            'message' => 'Success',
            'data' => $responseData,
            'pending' => $pendingItems,
            'history' => $historyItems,
            'history_data' => $historyItems,
            'all_data' => $items,
        ]);
    }

    /**
     * Keputusan Uji Mutu Quality Control (TF 2)
     * ATURAN BAKU: Hanya ada 2 pilihan: 'Good' atau 'Not Good' (Hold).
     * TIDAK ADA 17 PENGUJIAN DI QC. QC HANYA MEMILIH SEKALI KLIK.
     */
    public function submitTest(Request $request)
    {
        $request->validate([
            'assembly_id' => 'required|integer',
            'decision' => 'required|in:Good,Not Good,Hold', // Hanya 2 pilihan: Good atau Not Good (Hold)
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $assembly = Assembly::findOrFail($request->assembly_id);
            $inspector = auth()->user()?->username ?: 'qc_inspector';
            $isGood = ($request->decision === 'Good');

            // 1. VALIDASI KEPATUHAN ALUR: Wajib telah lolos perakitan dan lolos TF 1
            if ($assembly->status !== 'Good' || strtoupper($assembly->assy_test_status ?? '') !== 'GOOD') {
                return $this->errorResponse('Unit belum lolos Uji Fungsional 1 (TF 1) dengan status Good! Tidak dapat diverifikasi QC.', 400);
            }

            // 2. Kunci permanen jika sudah diverifikasi fisik oleh Gudang
            if (in_array(strtoupper($assembly->warehouse_receipt_status ?? ''), ['ACCEPTED', 'ACC', 'DECLINED'])) {
                return $this->errorResponse('Unit ini sudah diverifikasi fisik oleh Gudang dan terkunci permanen!', 400);
            }

            // Format nomor dokumen QC: WH/QC/xxxxxx (atau WH/QC/PU/angka jika perakitan ulang)
            $tray = Tray::find($assembly->tray_id);
            $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));
            if ($isPU) {
                preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);
                $puNum = !empty($m[1]) ? (int)$m[1] : 1;
                $qcDocNumber = "WH/QC/PU/{$puNum}";
            } else {
                $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                $qcDocNumber = "WH/QC/{$docSeq}";
            }

            $assembly->update([
                'qc_status' => $isGood ? 'GOOD' : 'HOLD_QC',
                'qc_failure_reasons' => $isGood ? null : ($request->input('notes') ?: 'Tertahan di Quality Control (QC)'),
                'qc_inspector_username' => $inspector,
                'qc_tested_at' => now(),
                'qc_doc_number' => $qcDocNumber,
            ]);

            // Jika Not Good di QC, update status part di master stock
            if (!$isGood) {
                foreach ([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn] as $sn) {
                    MasterStock::where('sn', trim($sn))->update([
                        'status' => 'Hold',
                        'last_reason' => $request->input('notes') ?: 'Tertahan di Quality Control (QC)',
                    ]);
                }

                PartAuditLog::create([
                    'part_type' => '15',
                    'sn' => $assembly->part15_sn,
                    'old_status' => 'Good',
                    'new_status' => 'HOLD_QC',
                    'action_type' => 'QC_DECISION_HOLD',
                    'actor_username' => $inspector,
                    'actor_role' => 'qc',
                    'reason' => $request->input('notes') ?: 'Keputusan QC: Not Good',
                ]);
            }

            return $this->successResponse($assembly, $isGood ? 'Unit Lolos Quality Control (Good).' : 'Unit Tertahan di Quality Control (Hold).');
        });
    }

    /**
     * Alias untuk items()
     */
    public function getItems(Request $request)
    {
        return $this->items($request);
    }

    /**
     * Daftar Unit yang Tertahan (Hold Items) di QC atau Perakitan
     */
    public function getHoldItems(Request $request)
    {
        $productType = $request->input('product_type');
        $source = strtoupper(trim($request->input('source', '')));
        $q = trim($request->input('q', ''));

        $query = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->select([
                'a.id as assembly_id',
                'a.tray_id',
                'a.set_number',
                'a.part15_sn',
                'a.part12_sn',
                'a.part1_sn',
                'a.status as assembly_status',
                'a.qc_status',
                'a.verified_status',
                'a.reason as assembly_reason',
                'a.qc_failure_reasons',
                'a.qc_notes',
                'a.rework_reason',
                'a.started_at',
                'a.finished_at',
                'a.qc_tested_at',
                'a.assy_doc_number',
                'a.qc_doc_number',
                't.tray_code',
                't.product_type',
            ])
            ->where(function ($w) {
                $w->whereIn('a.qc_status', ['HOLD_QC', 'HOLD_ASSY', 'REWORK_QC', 'RU'])
                  ->orWhere('a.status', 'Hold')
                  ->orWhere('a.status', 'Dirakit Ulang')
                  ->orWhereIn('a.verified_status', ['HOLD_QC_GUDANG', 'WAITING_REASSEMBLY', 'HOLD_GUDANG', 'PENDING_ADMIN', 'PENDING_ADMIN_QC']);
            });

        if ($productType && $productType !== 'ALL') {
            $query->where('t.product_type', $productType);
        }

        if ($source === 'QC') {
            $query->where(function ($w) {
                $w->whereIn('a.qc_status', ['HOLD_QC', 'REWORK_QC'])
                  ->orWhere('a.verified_status', 'HOLD_QC_GUDANG');
            });
        } elseif ($source === 'ASSY' || $source === 'TESTER_ASSEMBLY') {
            $query->where(function ($w) {
                $w->where('a.qc_status', 'HOLD_ASSY')
                  ->orWhere('a.status', 'Hold')
                  ->orWhere('a.status', 'Dirakit Ulang');
            });
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('a.part15_sn', 'like', "%{$q}%")
                  ->orWhere('a.part12_sn', 'like', "%{$q}%")
                  ->orWhere('a.part1_sn', 'like', "%{$q}%")
                  ->orWhere('t.tray_code', 'like', "%{$q}%")
                  ->orWhere('a.reason', 'like', "%{$q}%")
                  ->orWhere('a.qc_failure_reasons', 'like', "%{$q}%");
            });
        }

        $items = $query->orderBy('a.id', 'desc')->get();
        return $this->successResponse($items);
    }

    /**
     * Persetujuan / Tindakan Hold Item oleh Administrator
     */
    public function approveHoldItem(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        $decision = strtoupper($request->input('decision', 'APPROVE_GOOD'));
        $notes = $request->input('notes', '');

        $assembly = Assembly::findOrFail($assemblyId);

        if (in_array($decision, ['APPROVE', 'GOOD', 'APPROVE_GOOD'])) {
            $assembly->update([
                'qc_status' => 'GOOD',
                'status' => 'Good',
                'qc_notes' => $notes ?: 'Disetujui oleh Administrator',
            ]);
        } elseif (in_array($decision, ['SET_REWORK', 'REWORK'])) {
            $assembly->update([
                'qc_status' => 'REWORK_QC',
                'status' => 'Rework',
                'rework_reason' => $notes ?: 'Diminta perbaikan/rework oleh Administrator',
            ]);
        }

        return $this->successResponse($assembly, 'Status hold item berhasil diperbarui');
    }
}

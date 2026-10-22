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
                'a.reason',
                'a.rework_reason',
                'a.test_checklist as assy_test_checklist', // Rekap hasil 17 checklist TF 1 untuk ditampilkan Read-Only di TF 2
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

        if ($status === 'pending') {
            $query->whereNull('a.qc_status');
        } elseif ($status === 'history') {
            $query->whereNotNull('a.qc_status');
        }

        $items = $query->orderBy('a.id', 'desc')->get()->map(function ($item) {
            $item->assy_test_checklist = json_decode($item->assy_test_checklist ?: '[]');
            return $item;
        });

        return $this->successResponse($items);
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

            // Format nomor dokumen QC: WH/QC/xxxxxx
            $tray = Tray::find($assembly->tray_id);
            $docSeq = str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
            $qcDocNumber = "WH/QC/{$docSeq}";

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
}

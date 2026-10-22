<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tray;
use App\Models\Part;
use App\Models\Assembly;
use App\Models\MasterStock;
use App\Models\PartAuditLog;
use App\Models\ReRegisterRequest;

class WarehouseController extends Controller
{
    /**
     * Mengambil daftar seluruh Tray aktif/riwayat
     */
    public function getTrays(Request $request)
    {
        $status = $request->input('status');
        $operator = $request->input('operator');

        $query = Tray::withCount(['parts', 'assemblies'])
            ->orderBy('id', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($operator) {
            $query->where('assigned_operator', $operator);
        }

        $trays = $query->get()->map(function ($tray) {
            $finishedCount = $tray->assemblies()->whereNotNull('finished_at')->count();
            $goodCount = $tray->assemblies()->where('status', 'Good')->count();
            $holdCount = $tray->assemblies()->where('status', 'Hold')->count();
            $scrapCount = $tray->assemblies()->where('status', 'Scrap')->count();
            $reworkCount = $tray->assemblies()->where('status', 'Rework')->count();

            return [
                'id' => $tray->id,
                'tray_code' => $tray->tray_code,
                'pic_warehouse' => $tray->pic_warehouse,
                'assigned_operator' => $tray->assigned_operator,
                'source_document_mo' => $tray->source_document_mo,
                'product_type' => $tray->product_type,
                'status' => $tray->status,
                'session_status' => $tray->session_status,
                'verified_by_gudang' => (bool)$tray->verified_by_gudang,
                'verified_at' => $tray->verified_at,
                'verified_by_user' => $tray->verified_by_user,
                'total_sets' => 22,
                'finished_count' => $finishedCount,
                'good_count' => $goodCount,
                'hold_count' => $holdCount,
                'scrap_count' => $scrapCount,
                'rework_count' => $reworkCount,
                'created_at' => $tray->created_at,
            ];
        });

        return $this->successResponse($trays);
    }

    /**
     * Membuat Tray Baru & Mengalokasikan 22 Set Part (P15, P1, P12)
     * Dijalankan dalam Database Transaction ACID untuk mencegah tabrakan alokasi
     */
    public function createTray(Request $request)
    {
        $request->validate([
            'tray_code' => 'required|string|unique:trays,tray_code',
            'pic_warehouse' => 'required|string',
            'assigned_operator' => 'nullable|string',
            'source_document_mo' => 'nullable|string',
            'product_type' => 'nullable|string',
            'parts' => 'required|array', // Array of { part_type, sn }
        ]);

        return DB::transaction(function () use ($request) {
            $tray = Tray::create([
                'tray_code' => trim($request->tray_code),
                'pic_warehouse' => trim($request->pic_warehouse),
                'assigned_operator' => $request->assigned_operator ? trim($request->assigned_operator) : null,
                'source_document_mo' => $request->source_document_mo ? trim($request->source_document_mo) : null,
                'product_type' => $request->product_type ?: 'Nanggala P9',
                'status' => 'active',
                'session_status' => 'active',
            ]);

            $partsData = [];
            $now = now();
            $actor = auth()->user()?->username ?: $request->pic_warehouse;

            foreach ($request->parts as $p) {
                $ptype = (string)$p['part_type'];
                $sn = trim((string)$p['sn']);

                // Validasi dan update status master stock menjadi 'Supplied'
                $stock = MasterStock::where('part_type', $ptype)
                    ->where(function ($w) use ($sn) {
                        $w->where('sn', $sn)
                          ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                    })
                    ->lockForUpdate() // InnoDB row-level locking
                    ->first();

                if ($stock) {
                    $stock->update([
                        'status' => 'Supplied',
                        'allocated_tray_id' => $tray->id,
                        'allocated_at' => $now,
                    ]);
                }

                $partsData[] = [
                    'tray_id' => $tray->id,
                    'part_type' => $ptype,
                    'sn' => $sn,
                    'status' => 'Supplied',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Part::insert($partsData);

            // Rekam audit log alokasi
            PartAuditLog::create([
                'part_type' => 'ALL',
                'sn' => $tray->tray_code,
                'old_status' => 'Tersedia',
                'new_status' => 'Supplied',
                'action_type' => 'CREATE_TRAY_ALLOCATION',
                'actor_username' => $actor,
                'actor_role' => 'gudang',
                'reason' => "Alokasi 22 Set Part ke Tray {$tray->tray_code}",
            ]);

            return $this->successResponse($tray, 'Tray supply berhasil dibuat dan part dialokasikan.', 201);
        });
    }

    /**
     * Detail Tray dan seluruh part/assemblies di dalamnya
     */
    public function getTrayDetail(Request $request)
    {
        $trayId = $request->input('id');
        $trayCode = $request->input('tray_code');

        $tray = Tray::where(function ($w) use ($trayId, $trayCode) {
            if ($trayId) $w->where('id', $trayId);
            if ($trayCode) $w->orWhere('tray_code', $trayCode);
        })->first();

        if (!$tray) {
            return $this->errorResponse('Tray tidak ditemukan', 404);
        }

        $parts = $tray->parts()->get();
        $assemblies = $tray->assemblies()->orderBy('set_number', 'asc')->get();

        return $this->successResponse([
            'tray' => $tray,
            'parts' => $parts,
            'assemblies' => $assemblies,
        ]);
    }

    /**
     * Verifikasi Fisik Tray dari Meja Kerja Assembly oleh Pihak Gudang
     */
    public function verifyTray(Request $request)
    {
        $trayId = $request->input('tray_id');
        $tray = Tray::findOrFail($trayId);

        $tray->update([
            'verified_by_gudang' => true,
            'verified_at' => now(),
            'verified_by_user' => auth()->user()?->username ?: 'Gudang',
        ]);

        return $this->successResponse($tray, 'Tray berhasil diverifikasi oleh Gudang.');
    }

    /**
     * Update Nomor MO pada Tray
     * Endpoint: POST /api/tray/update_mo
     */
    public function updateMo(Request $request)
    {
        $request->validate([
            'tray_id' => 'required|integer',
            'mo' => 'required|string',
        ]);

        $tray = Tray::findOrFail($request->tray_id);
        $tray->update(['source_document_mo' => trim($request->mo)]);

        return $this->successResponse($tray, 'Nomor MO berhasil diperbarui.');
    }

    /**
     * Verifikasi Penerimaan Fisik Gudang dari Hasil Uji Mutu QC
     * Endpoint: POST /api/warehouse/verify-qc-receipt
     */
    public function verifyQcReceipt(Request $request)
    {
        $request->validate([
            'assembly_id' => 'required|integer',
            'action' => 'required|in:ACCEPT,DECLINE',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $assembly = Assembly::with('tray')->findOrFail($request->assembly_id);
            $action = strtoupper($request->action);
            $user = auth()->user()?->username ?: 'Gudang';
            $notes = trim($request->input('notes', ''));

            if (!in_array(strtoupper($assembly->qc_status ?? ''), ['GOOD', 'GOOD_QC'])) {
                return $this->errorResponse('Gudang hanya dapat memverifikasi barang yang status akhirnya Good dari QC!', 400);
            }

            if ($assembly->warehouse_receipt_status && in_array(strtoupper($assembly->warehouse_receipt_status), ['ACCEPTED', 'ACC', 'DECLINED'])) {
                return $this->errorResponse('Sudah diverifikasi fisik oleh Gudang dan terkunci permanen.', 400);
            }

            if ($action === 'ACCEPT') {
                $grDoc = "WH/GR/" . str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                $assembly->update([
                    'warehouse_receipt_status' => 'ACCEPTED',
                    'warehouse_receipt_at' => now(),
                    'warehouse_receipt_by' => $user,
                    'warehouse_doc_number' => $assembly->warehouse_doc_number ?: $grDoc,
                    'declined_reason' => null,
                    'is_declined' => false,
                    'verified_status' => 'ACC',
                ]);

                // Batalkan re_register_requests pending jika ada
                ReRegisterRequest::whereIn('sn', array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]))
                    ->where('status', 'PENDING')
                    ->update(['status' => 'CANCELLED', 'admin_notes' => 'Accepted by warehouse']);

                $msg = "Penerimaan fisik P9 SN {$assembly->part15_sn} berhasil di-Accept oleh Gudang.";
            } else {
                $decReason = $notes ?: 'Ditolak fisik oleh gudang saat verifikasi penerimaan QC';
                $assembly->update([
                    'warehouse_receipt_status' => 'DECLINED',
                    'warehouse_receipt_at' => now(),
                    'warehouse_receipt_by' => $user,
                    'declined_reason' => $decReason,
                    'is_declined' => true,
                    'verified_status' => 'DECLINED',
                    'is_reregistered' => false,
                ]);

                foreach (['15' => $assembly->part15_sn, '1' => $assembly->part1_sn, '12' => $assembly->part12_sn] as $pType => $sn) {
                    if ($sn && $sn !== '-') {
                        MasterStock::where('part_type', $pType)
                            ->where(function ($w) use ($sn) {
                                $w->where('sn', $sn)
                                  ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                            })
                            ->update(['status' => 'Declined']);

                        PartAuditLog::create([
                            'part_type' => $pType,
                            'sn' => $sn,
                            'old_status' => 'Good/QC',
                            'new_status' => 'Declined',
                            'action_type' => 'WAREHOUSE_DECLINE_QC_RECEIPT',
                            'actor_username' => $user,
                            'actor_role' => 'gudang',
                            'reason' => $decReason,
                        ]);
                    }
                }

                $msg = "Penerimaan fisik P9 SN {$assembly->part15_sn} di-Decline oleh Gudang.";
            }

            // Cek apakah seluruh unit Good di tray ini sudah selesai diverifikasi
            $trayId = $assembly->tray_id;
            $totalGood = Assembly::where('tray_id', $trayId)
                ->whereIn('qc_status', ['GOOD', 'GOOD_QC'])
                ->count();
            $totalVerified = Assembly::where('tray_id', $trayId)
                ->whereIn('qc_status', ['GOOD', 'GOOD_QC'])
                ->whereIn('warehouse_receipt_status', ['ACCEPTED', 'DECLINED'])
                ->count();

            if ($totalGood > 0 && $totalGood === $totalVerified) {
                Tray::where('id', $trayId)->update([
                    'verified_by_gudang' => true,
                    'verified_by_user' => $user,
                    'verified_at' => now(),
                ]);
            }

            return $this->successResponse($assembly, $msg);
        });
    }

    /**
     * Riwayat Tray yang Dikembalikan
     * Endpoint: GET /api/gudang/returned-trays
     */
    public function getReturnedTrays()
    {
        $trays = Tray::where('status', 'returned')
            ->orWhereNotNull('return_verified_at')
            ->orderBy('id', 'desc')
            ->get();

        return $this->successResponse($trays);
    }
}

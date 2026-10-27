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

        $trayList = $query->get();
        if ($trayList->isEmpty()) {
            return $this->successResponse([]);
        }

        $trayIds = $trayList->pluck('id')->all();
        $stats = DB::table('assemblies')
            ->whereIn('tray_id', $trayIds)
            ->select('tray_id',
                DB::raw('COUNT(CASE WHEN finished_at IS NOT NULL OR (status IS NOT NULL AND status != "") THEN 1 END) as finished_count'),
                DB::raw('COUNT(CASE WHEN status = "Good" THEN 1 END) as good_count'),
                DB::raw('COUNT(CASE WHEN status = "Hold" THEN 1 END) as hold_count'),
                DB::raw('COUNT(CASE WHEN status = "Scrap" THEN 1 END) as scrap_count'),
                DB::raw('COUNT(CASE WHEN status = "Rework" THEN 1 END) as rework_count')
            )
            ->groupBy('tray_id')
            ->get()
            ->keyBy('tray_id');

        $trays = $trayList->map(function ($tray) use ($stats) {
            $stat = $stats->get($tray->id);
            $finishedCount = $stat ? (int)$stat->finished_count : 0;
            $goodCount = $stat ? (int)$stat->good_count : 0;
            $holdCount = $stat ? (int)$stat->hold_count : 0;
            $scrapCount = $stat ? (int)$stat->scrap_count : 0;
            $reworkCount = $stat ? (int)$stat->rework_count : 0;

            return [
                'id' => $tray->id,
                'tray_code' => $tray->tray_code,
                'pic_warehouse' => $tray->pic_warehouse,
                'assigned_operator' => $tray->assigned_operator,
                'source_document_mo' => $tray->source_document_mo,
                'product_type' => $tray->product_type,
                'status' => $tray->status,
                'session_status' => ($finishedCount >= 22 && !in_array($tray->session_status, ['COMPLETED_SHIFT', 'RETURNED_TO_WAREHOUSE'])) ? 'COMPLETED' : $tray->session_status,
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
        if (!$request->filled('tray_code')) {
            $isPU = $request->boolean('is_pu') || str_contains((string)$request->input('source_document_mo'), 'PU');
            if ($isPU) {
                $lastPu = Tray::where(function ($q) {
                    $q->where('tray_code', 'LIKE', 'WH/ASSY-PU/%')
                      ->orWhere('tray_code', 'LIKE', 'WH/ASSY/PU/%');
                })->orderBy('id', 'desc')->first();

                $nextPuNum = 1;
                if ($lastPu && preg_match('/(\d+)$/', $lastPu->tray_code, $m)) {
                    $nextPuNum = (int)$m[1] + 1;
                }
                $trayCode = 'WH/ASSY-PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);
                while (Tray::where('tray_code', $trayCode)->exists()) {
                    $nextPuNum++;
                    $trayCode = 'WH/ASSY-PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);
                }
            } else {
                $lastTray = Tray::where('tray_code', 'LIKE', 'WH/ASSY/%')
                    ->where('tray_code', 'NOT LIKE', '%PU%')
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNum = 1;
                if ($lastTray && preg_match('/(\d+)$/', $lastTray->tray_code, $m)) {
                    $nextNum = (int)$m[1] + 1;
                }
                $trayCode = 'WH/ASSY/' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                while (Tray::where('tray_code', $trayCode)->exists()) {
                    $nextNum++;
                    $trayCode = 'WH/ASSY/' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                }
            }
            $request->merge(['tray_code' => $trayCode]);
        }

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

            // 1. Validasi anti-duplikasi di payload permintaan supply
            $seenInPayload = [];
            foreach ($request->parts as $p) {
                $pKey = (string)$p['part_type'] . ':' . trim((string)$p['sn']);
                if (isset($seenInPayload[$pKey])) {
                    return $this->errorResponse("Nomor seri {$p['sn']} (Tipe {$p['part_type']}) duplikat di dalam daftar supply tray!", 400);
                }
                $seenInPayload[$pKey] = true;
            }

            $partsData = [];
            $now = now();
            $actor = auth()->user()?->username ?: $request->pic_warehouse;

            foreach ($request->parts as $p) {
                $ptype = (string)$p['part_type'];
                $sn = trim((string)$p['sn']);

                // Validasi dan lock row master stock
                $stock = MasterStock::where('part_type', $ptype)
                    ->where(function ($w) use ($sn) {
                        $w->where('sn', $sn)
                          ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                    })
                    ->lockForUpdate() // InnoDB row-level locking
                    ->first();

                if (!$stock) {
                    return $this->errorResponse("Part {$ptype} SN {$sn} tidak terdaftar di Master Stok!", 400);
                }

                // VALIDASI MUTLAK: Part wajib berstatus 'Tersedia' dan belum teralokasi ke tray manapun
                if ($stock->status !== 'Tersedia' || !empty($stock->allocated_tray_id)) {
                    return $this->errorResponse("Part {$ptype} SN {$sn} tidak dapat dialokasikan karena status saat ini adalah '{$stock->status}' atau sedang terikat pada baki lain!", 400);
                }

                $stock->update([
                    'status' => 'Supplied',
                    'allocated_tray_id' => $tray->id,
                    'allocated_at' => $now,
                ]);

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
        $trayCode = $request->input('code') ?: $request->input('tray_code');

        if (!$trayId && !$trayCode) {
            $tray = Tray::orderBy('id', 'desc')->first();
        } else {
            $tray = Tray::where(function ($w) use ($trayId, $trayCode) {
                if ($trayId) $w->where('id', $trayId);
                if ($trayCode) $w->orWhere('tray_code', $trayCode);
            })->first();
        }

        if (!$tray) {
            return $this->errorResponse('Tray tidak ditemukan', 404);
        }

        $parts = $tray->parts()->orderBy('id', 'asc')->get();
        $assemblies = $tray->assemblies()->orderBy('set_number', 'asc')->get();

        // Active downtime (pause)
        $activeDt = \App\Models\AssemblyDowntime::where('tray_id', $tray->id)
            ->whereNull('resumed_at')
            ->orderBy('id', 'desc')
            ->first();
        $activeDowntime = null;
        if ($activeDt) {
            $elapsedSeconds = max(0, now()->diffInSeconds($activeDt->paused_at));
            $activeDowntime = [
                'is_paused' => true,
                'downtime_id' => $activeDt->id,
                'reason' => $activeDt->reason,
                'operator' => $activeDt->operator,
                'paused_at' => $activeDt->paused_at,
                'elapsed_seconds' => $elapsedSeconds,
            ];
        }

        // Active in-progress set
        $inProgress = \Illuminate\Support\Facades\DB::table('assembly_in_progress')
            ->where('tray_id', $tray->id)
            ->first();

        return $this->successResponse([
            'tray' => $tray,
            'parts' => $parts,
            'assemblies' => $assemblies,
            'active_downtime' => $activeDowntime,
            'in_progress' => $inProgress,
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
                $tray = Tray::find($assembly->tray_id);
                $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));
                if ($isPU) {
                    preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);
                    $puNum = !empty($m[1]) ? (int)$m[1] : 1;
                    $grDoc = "WH/GR/PU/{$puNum}";
                } else {
                    $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                    $grDoc = "WH/GR/{$docSeq}";
                }
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

    /**
     * Detail Pengembalian Tray & Daftar Part
     * Endpoint: GET /api/gudang/returned-tray-detail
     */
    public function getReturnedTrayDetail(Request $request)
    {
        $trayId = $request->input('tray_id') ?: $request->input('id');
        $trayCode = $request->input('code') ?: $request->input('tray_code');

        $tray = Tray::where(function ($w) use ($trayId, $trayCode) {
            if ($trayId) $w->where('id', $trayId);
            if ($trayCode) $w->orWhere('tray_code', $trayCode);
        })->first();

        if (!$tray) {
            return $this->errorResponse('Tray tidak ditemukan', 404);
        }

        $assemblies = Assembly::where('tray_id', $tray->id)->orderBy('set_number', 'asc')->get();
        $parts = DB::table('parts')->where('tray_id', $tray->id)->get();

        $sets = [];
        for ($i = 1; $i <= 22; $i++) {
            $assy = $assemblies->firstWhere('set_number', $i);
            $p15 = $assy?->part15_sn ?: ($parts->where('part_type', '15')->slice($i - 1, 1)->first()?->sn ?? '-');
            $p1 = $assy?->part1_sn ?: ($parts->where('part_type', '1')->slice($i - 1, 1)->first()?->sn ?? '-');
            $p12 = $assy?->part12_sn ?: ($parts->where('part_type', '12')->slice($i - 1, 1)->first()?->sn ?? '-');

            $sets[] = [
                'set_number' => $i,
                'p15_sn' => $p15,
                'p1_sn' => $p1,
                'p12_sn' => $p12,
                'status' => $assy?->status ?: 'Tersedia',
                'is_verified' => $assy ? in_array($assy->verified_status, ['ACC', 'RETURN_VERIFIED']) : false,
                'is_declined' => $assy ? (bool)$assy->is_declined : false,
                'declined_reason' => $assy?->declined_reason,
            ];
        }

        return response()->json([
            'ok' => true,
            'tray' => $tray,
            'finished_count' => $assemblies->whereNotNull('finished_at')->count(),
            'sets' => $sets,
        ]);
    }

    /**
     * Override Status Part Langsung dari Gudang
     * Endpoint: POST /api/gudang/override-status
     */
    public function overrideStatus(Request $request)
    {
        $partType = (string)$request->input('part_type', '');
        $sn = trim((string)$request->input('sn', ''));
        $newStatus = $request->input('new_status', 'Tersedia');
        $reason = $request->input('reason', '');
        $user = auth()->user()?->username ?: 'Gudang';

        if (!$sn) {
            return response()->json(['ok' => false, 'error' => 'SN part wajib diisi.'], 400);
        }

        return DB::transaction(function () use ($partType, $sn, $newStatus, $reason, $user) {
            $stock = MasterStock::where('part_type', $partType)
                ->where(function ($w) use ($sn) {
                    $w->where('sn', $sn)
                      ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                })
                ->first();

            if ($stock) {
                $oldStatus = $stock->status;
                $isTersedia = ($newStatus === 'Tersedia');
                $stock->update([
                    'status' => $newStatus,
                    'allocated_tray_id' => $isTersedia ? null : $stock->allocated_tray_id,
                    'allocated_at' => $isTersedia ? null : $stock->allocated_at,
                    'used_at' => $isTersedia ? null : $stock->used_at,
                    'last_tray_id' => $isTersedia ? null : $stock->last_tray_id,
                    'is_replaced_from_assy' => $isTersedia ? 0 : $stock->is_replaced_from_assy,
                    'last_reason' => $reason,
                ]);

                PartAuditLog::create([
                    'part_type' => $partType,
                    'sn' => $sn,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'action_type' => 'WAREHOUSE_OVERRIDE_STATUS',
                    'actor_username' => $user,
                    'actor_role' => 'gudang',
                    'reason' => $reason ?: 'Override status oleh Gudang',
                ]);
            }

            return response()->json([
                'ok' => true,
                'message' => "Status part {$sn} berhasil diubah menjadi {$newStatus}!"
            ]);
        });
    }

    /**
     * Gudang ACC / Verifikasi Individual Set Perakitan
     * Endpoint: POST /api/gudang/verify-set
     */
    public function verifySet(Request $request)
    {
        $trayId = $request->input('tray_id');
        $setNum = $request->input('set_number');
        $action = $request->input('action', 'ACC');
        $status = $request->input('status', 'Good');
        $defectiveParts = $request->input('defective_parts', []);
        $reason = $request->input('reason', '');

        $assembly = Assembly::where('tray_id', $trayId)->where('set_number', $setNum)->first();
        if ($assembly) {
            $assembly->update([
                'verified_status' => 'ACC',
                'status' => $status,
                'defective_parts' => json_encode($defectiveParts),
                'reason' => $reason,
                'is_declined' => false,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => "Set #{$setNum} berhasil di-ACC ({$status})."
        ]);
    }

    /**
     * Verifikasi Fisik 1-Set Pengembalian Tray
     * Endpoint: POST /api/gudang/verify-return-set
     */
    public function verifyReturnSet(Request $request)
    {
        $trayId = $request->input('tray_id');
        $setNum = $request->input('set_number');
        $p15Sn = trim((string)$request->input('p15_sn', ''));
        $p1Sn = trim((string)$request->input('p1_sn', ''));
        $p12Sn = trim((string)$request->input('p12_sn', ''));
        $user = auth()->user()?->username ?: 'Gudang';

        return DB::transaction(function () use ($trayId, $setNum, $p15Sn, $p1Sn, $p12Sn, $user) {
            foreach ([['15', $p15Sn], ['1', $p1Sn], ['12', $p12Sn]] as [$pType, $sn]) {
                if ($sn && $sn !== '-') {
                    MasterStock::where('part_type', $pType)
                        ->where(function ($w) use ($sn) {
                            $w->where('sn', $sn)
                              ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                        })
                        ->update([
                            'status' => 'Tersedia',
                            'allocated_tray_id' => null,
                            'allocated_at' => null,
                            'used_at' => null,
                            'last_tray_id' => null,
                            'is_replaced_from_assy' => 0,
                            'last_reason' => 'Pengembalian tray diverifikasi Gudang (kembali Tersedia)',
                        ]);

                    PartAuditLog::create([
                        'part_type' => $pType,
                        'sn' => $sn,
                        'old_status' => 'Supplied/Return',
                        'new_status' => 'Tersedia',
                        'action_type' => 'RETURN_VERIFY_SET',
                        'actor_username' => $user,
                        'actor_role' => 'gudang',
                        'reason' => 'Verifikasi pengembalian part oleh Gudang',
                    ]);
                }
            }

            Assembly::where('tray_id', $trayId)->where('set_number', $setNum)->update([
                'verified_status' => 'RETURN_VERIFIED'
            ]);

            $totalSets = Assembly::where('tray_id', $trayId)->count();
            $verifiedSets = Assembly::where('tray_id', $trayId)->where('verified_status', 'RETURN_VERIFIED')->count();
            $isFullyVerified = ($totalSets > 0 && $totalSets === $verifiedSets);

            if ($isFullyVerified) {
                Tray::where('id', $trayId)->update([
                    'return_verified' => true,
                    'return_verified_by' => $user,
                    'return_verified_at' => now(),
                    'status' => 'returned',
                ]);
            }

            return response()->json([
                'ok' => true,
                'message' => "Set #{$setNum} berhasil diverifikasi.",
                'is_fully_verified' => $isFullyVerified
            ]);
        });
    }

    /**
     * Decline 1-Set Pengembalian Tray
     * Endpoint: POST /api/gudang/decline-return-set
     */
    public function declineReturnSet(Request $request)
    {
        $trayId = $request->input('tray_id');
        $setNum = $request->input('set_number');
        $declinedParts = $request->input('declined_parts', ['15', '1', '12']);
        $p15Sn = trim((string)$request->input('p15_sn', ''));
        $p1Sn = trim((string)$request->input('p1_sn', ''));
        $p12Sn = trim((string)$request->input('p12_sn', ''));
        $reason = $request->input('reason', 'Ditolak/Decline saat verifikasi pengembalian');
        $user = auth()->user()?->username ?: 'Gudang';

        return DB::transaction(function () use ($trayId, $setNum, $declinedParts, $p15Sn, $p1Sn, $p12Sn, $reason, $user) {
            Assembly::where('tray_id', $trayId)->where('set_number', $setNum)->update([
                'is_declined' => true,
                'declined_reason' => $reason,
                'status' => 'Decline',
                'verified_status' => 'DECLINED',
            ]);

            $partMap = ['15' => $p15Sn, '1' => $p1Sn, '12' => $p12Sn];
            foreach ($declinedParts as $pType) {
                $sn = $partMap[$pType] ?? null;
                if ($sn && $sn !== '-') {
                    MasterStock::where('part_type', $pType)
                        ->where(function ($w) use ($sn) {
                            $w->where('sn', $sn)
                              ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                        })
                        ->update([
                            'status' => 'Decline',
                            'last_reason' => $reason,
                        ]);

                    PartAuditLog::create([
                        'part_type' => $pType,
                        'sn' => $sn,
                        'old_status' => 'Supplied/Return',
                        'new_status' => 'Decline',
                        'action_type' => 'DECLINE_RETURN_SET',
                        'actor_username' => $user,
                        'actor_role' => 'gudang',
                        'reason' => $reason,
                    ]);
                }
            }

            return response()->json([
                'ok' => true,
                'message' => 'Set berhasil di-decline dan masuk antrean Verifikasi Admin.'
            ]);
        });
    }

    /**
     * Arsipkan Notifikasi Pengembalian Tray
     * Endpoint: POST /api/gudang/acknowledge-return
     */
    public function acknowledgeReturn(Request $request)
    {
        $trayId = $request->input('tray_id');
        Tray::where('id', $trayId)->update([
            'status' => 'returned',
            'session_status' => 'ARCHIVED',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Tray pengembalian telah diarsipkan.'
        ]);
    }

    /**
     * Alias Simpan Tray Baru (Gudang Save)
     * Endpoint: POST /api/gudang/save
     */
    public function saveGudang(Request $request)
    {
        return $this->createTray($request);
    }
}


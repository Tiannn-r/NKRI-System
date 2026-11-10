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



        $query = Tray::withCount([
            'parts',
            'assemblies' => function ($q) {
                $q->where(function ($w) {
                    $w->where('is_declined', 0)->orWhereNull('is_declined');
                })->where(function ($w) {
                    $w->whereNull('status')->orWhereNotIn('status', ['Decline', 'DECLINED']);
                });
            }
        ])



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



                DB::raw('COUNT(CASE WHEN (status IS NULL OR status NOT IN ("Decline", "DECLINED")) AND (finished_at IS NOT NULL OR (status IS NOT NULL AND status != "" AND status NOT IN ("TERSUPPLY", "Dikembalikan ke Gudang", "RETURNED"))) THEN 1 END) as finished_count'),

                DB::raw('COUNT(CASE WHEN COALESCE(original_assembly_status, status) = "Good" AND (is_reregistered IS NULL OR is_reregistered = 0) THEN 1 END) as good_count'),

                DB::raw('COUNT(CASE WHEN COALESCE(original_assembly_status, status) = "Hold" THEN 1 END) as hold_count'),

                DB::raw('COUNT(CASE WHEN COALESCE(original_assembly_status, status) = "Scrap" THEN 1 END) as scrap_count'),



                DB::raw('COUNT(CASE WHEN status = "Rework" THEN 1 END) as rework_count'),



                DB::raw('COUNT(CASE WHEN (COALESCE(original_assembly_status, status) = "Good" OR status = "Good" OR assy_doc_number IS NOT NULL) AND (is_reregistered IS NULL OR is_reregistered = 0) AND (assy_doc_number IS NOT NULL OR (assy_test_status IS NOT NULL AND assy_test_status NOT IN ("PENDING", "")) OR qc_status IN ("HOLD_ASSY", "REWORK_ASSY", "PENDING_QC", "GOOD", "HOLD_QC")) THEN 1 END) as tested_count'),



                DB::raw('COUNT(CASE WHEN (COALESCE(original_assembly_status, status) = "Good" OR status = "Good" OR assy_doc_number IS NOT NULL) AND (is_reregistered IS NULL OR is_reregistered = 0) AND (assy_doc_number IS NOT NULL OR assy_test_status = "Good" OR qc_status IN ("GOOD", "PENDING_QC", "HOLD_QC")) AND (assy_test_status IS NULL OR assy_test_status NOT IN ("Hold", "Hold TF 1", "HOLD TF 1", "HOLD", "NOT_GOOD", "RU")) AND (qc_status IS NULL OR qc_status NOT IN ("HOLD_ASSY", "RU", "REWORK_ASSY")) AND (assy_failure_reasons IS NULL OR assy_failure_reasons = "") THEN 1 END) as good_assy_test_count'),

                DB::raw('COUNT(CASE WHEN (COALESCE(original_assembly_status, status) = "Good" OR status = "Good" OR assy_doc_number IS NOT NULL) AND (is_reregistered IS NULL OR is_reregistered = 0) AND (assy_test_status IN ("Hold", "Hold TF 1", "HOLD TF 1", "HOLD", "NOT_GOOD", "Not Good") OR qc_status = "HOLD_ASSY" OR (assy_failure_reasons IS NOT NULL AND assy_failure_reasons != "")) AND (assy_test_status != "RU" AND (qc_status IS NULL OR qc_status != "RU")) THEN 1 END) as hold_assy_test_count'),

                DB::raw('MAX(set_number) as max_set_number'),
                DB::raw('COUNT(CASE WHEN is_reregistered = 1 OR status = "Dirakit Ulang" OR qc_status = "RU" OR rereg_wo_suffix IS NOT NULL THEN 1 END) as ru_count'),
                DB::raw('COUNT(CASE WHEN assy_doc_number IS NOT NULL AND assy_doc_number != "" THEN 1 END) as assy_doc_count'),
                DB::raw('COUNT(CASE WHEN qc_doc_number IS NOT NULL AND qc_doc_number != "" THEN 1 END) as qc_doc_count'),
                DB::raw('COUNT(CASE WHEN qc_status = "GOOD" THEN 1 END) as good_qc_count'),
                DB::raw('COUNT(CASE WHEN qc_status LIKE "%HOLD%" THEN 1 END) as hold_qc_count'),
                DB::raw('COUNT(CASE WHEN (warehouse_doc_number IS NOT NULL AND warehouse_doc_number != "") OR warehouse_receipt_status IN ("ACCEPTED", "ACC", "DECLINED") THEN 1 END) as gr_doc_count'),
                DB::raw('COUNT(CASE WHEN warehouse_receipt_status IN ("ACCEPTED", "ACC") THEN 1 END) as good_gr_count'),
                DB::raw('MAX(COALESCE(finished_at, started_at, created_at)) as latest_assembly_at'),
                DB::raw('MIN(COALESCE(started_at, finished_at, created_at)) as earliest_assembly_at')
            )



            ->groupBy('tray_id')



            ->get()



            ->keyBy('tray_id');



        $trays = $trayList->map(function ($tray) use ($stats) {



            $stat = $stats->get($tray->id);



            $goodCount = $stat ? (int)$stat->good_count : 0;

            $holdCount = $stat ? (int)$stat->hold_count : 0;

            $scrapCount = $stat ? (int)$stat->scrap_count : 0;

            $finishedCount = $stat ? max((int)$stat->finished_count, $goodCount + $holdCount + $scrapCount) : 0;



            $reworkCount = $stat ? (int)$stat->rework_count : 0;



            $testedCount = $stat ? (int)$stat->tested_count : 0;



            $goodAssyTestCount = $stat ? (int)$stat->good_assy_test_count : 0;
            $holdAssyTestCount = $stat ? (int)$stat->hold_assy_test_count : 0;



            $ruCount = $stat ? (int)$stat->ru_count : 0;

            $partsCount = (int)($tray->parts_count ?: 0);
            $partsTotalSets = (int)ceil($partsCount / 3);
            $maxSet = $stat ? (int)$stat->max_set_number : 0;
            $computedTotalSets = max($partsTotalSets, (int)$tray->assemblies_count, $maxSet, ($stat ? (int)$stat->finished_count : 0));
            $isSessionCompleted = ($computedTotalSets > 0 && $finishedCount >= $computedTotalSets && !in_array($tray->session_status, ['COMPLETED_SHIFT', 'RETURNED_TO_WAREHOUSE']));

            return [

                'id' => $tray->id,

                'tray_code' => $tray->tray_code,

                'pic_warehouse' => $tray->pic_warehouse,

                'assigned_operator' => $tray->assigned_operator,

                'source_document_mo' => $tray->source_document_mo,

                'product_type' => $tray->product_type,

                'status' => $tray->status,

                'session_status' => $isSessionCompleted ? 'COMPLETED' : $tray->session_status,

                'verified_by_gudang' => (bool)$tray->verified_by_gudang,

                'verified_at' => $tray->verified_at,

                'verified_by_user' => $tray->verified_by_user,

                'total_sets' => $computedTotalSets > 0 ? $computedTotalSets : 22,
                'assemblies_count' => (int)$tray->assemblies_count,
                'parts_count' => (int)$tray->parts_count,



                'finished_count' => $finishedCount,



                'good_count' => $goodCount,



                'good_assembly_count' => $goodCount,



                'hold_count' => $holdCount,



                'scrap_count' => $scrapCount,



                'rework_count' => $reworkCount,



                'tested_count' => $testedCount,



                'good_assy_test_count' => $goodAssyTestCount,
                'hold_assy_test_count' => $holdAssyTestCount,



                'ru_count' => $ruCount,
                'assy_doc_count' => $stat ? (int)$stat->assy_doc_count : 0,
                'qc_doc_count' => $stat ? (int)$stat->qc_doc_count : 0,
                'good_qc_count' => $stat ? (int)$stat->good_qc_count : 0,
                'hold_qc_count' => $stat ? (int)$stat->hold_qc_count : 0,
                'gr_doc_count' => $stat ? (int)$stat->gr_doc_count : 0,
                'good_gr_count' => $stat ? (int)$stat->good_gr_count : 0,



                'latest_assembly_at' => $stat ? $stat->latest_assembly_at : null,
                'earliest_assembly_at' => $stat ? $stat->earliest_assembly_at : null,
                'updated_at' => $tray->updated_at,
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

                $allPuTrays = Tray::where(function ($q) {
                    $q->where('tray_code', 'LIKE', '%-PU%')
                      ->orWhere('tray_code', 'LIKE', '%/PU/%')
                      ->orWhere('tray_code', 'LIKE', 'WH/ASSY-PU/%');
                })->get(['tray_code']);

                $maxPuNum = 0;
                foreach ($allPuTrays as $pu) {
                    if (preg_match('/(?:-PU|\/PU\/|WH\/ASSY-PU\/)(\d+)/i', $pu->tray_code, $m)) {
                        $maxPuNum = max($maxPuNum, (int)$m[1]);
                    }
                }

                $nextPuNum = $maxPuNum + 1;
                $trayCode = 'WH/ASSY/PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);

                while (Tray::where('tray_code', $trayCode)->orWhere('tray_code', 'WH/ASSY-PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT))->exists()) {
                    $nextPuNum++;
                    $trayCode = 'WH/ASSY/PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);
                }

            } else {

                $allTrays = Tray::where('tray_code', 'LIKE', 'WH/ASSY/%')
                    ->where('tray_code', 'NOT LIKE', '%PU%')
                    ->get(['tray_code']);

                $maxNum = 0;
                foreach ($allTrays as $t) {
                    $cleanCode = preg_replace('/-S\d+$/i', '', $t->tray_code);
                    if (preg_match('/WH\/ASSY\/(\d+)/i', $cleanCode, $m)) {
                        $maxNum = max($maxNum, (int)$m[1]);
                    }
                }

                $nextNum = $maxNum + 1;
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



            $actorData = $this->resolveActor($request);
            $actor = $actorData['full_name'];



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
                if ($trayCode) {
                    $w->orWhere('tray_code', $trayCode);
                    if (preg_match('/PU[^\d]*(\d+)/i', $trayCode, $m)) {
                        $puNum = (int)$m[1];
                        $w->orWhere('tray_code', 'LIKE', "%-PU{$puNum}")
                          ->orWhere('tray_code', 'LIKE', "%/PU/{$puNum}")
                          ->orWhere('tray_code', 'LIKE', "%-PU/" . str_pad($puNum, 6, '0', STR_PAD_LEFT))
                          ->orWhere('tray_code', 'LIKE', "%/PU/" . str_pad($puNum, 6, '0', STR_PAD_LEFT));
                    }
                }
            })->first();



        }



        if (!$tray) {



            return $this->errorResponse('Tray tidak ditemukan', 404);



        }



        $parts = $tray->parts()->orderBy('id', 'asc')->get();

        // Filter parts: Jangan sertakan part yang sudah dikembalikan ke gudang (Tersedia) atau dialokasikan ke baki lain
        $trayPartsSns = $parts->pluck('sn')->filter()->unique()->toArray();
        if (!empty($trayPartsSns)) {
            $validAllocatedSns = \App\Models\MasterStock::whereIn('sn', $trayPartsSns)
                ->where('allocated_tray_id', $tray->id)
                ->where('status', '!=', 'Tersedia')
                ->pluck('sn')
                ->toArray();
            $parts = $parts->filter(function ($p) use ($validAllocatedSns) {
                if ($p->status === 'Done') return true;
                return in_array($p->sn, $validAllocatedSns);
            })->values();
        }

        $assemblies = $tray->assemblies()->orderBy('set_number', 'asc')->get();

        // Filter assemblies: Jangan sertakan rakitan yang sudah di-restore/dipulihkan ke gudang tanpa pernah selesai dirakit
        $assemblies = $assemblies->filter(function ($a) {
            if (empty($a->finished_at) && in_array($a->verified_status, ['RESOLVED_RESTORED', 'RETURN_VERIFIED'])) {
                return false;
            }
            if (empty($a->finished_at) && $a->status === 'Dipulihkan') {
                return false;
            }
            return true;
        })->values();

        // Active in-progress set
        $inProgress = \Illuminate\Support\Facades\DB::table('assembly_in_progress')
            ->where('tray_id', $tray->id)
            ->first();

        // Susun daftar set lengkap dari part yang dialokasikan atau assembly yang sudah tercatat
        $assyMap = [];
        foreach ($assemblies as $a) {
            $assyMap[$a->set_number] = $a;
        }

        $p15List = $parts->where('part_type', '15')->sortBy('sn')->values();
        $p1List = $parts->where('part_type', '1')->sortBy('sn')->values();
        $p12List = $parts->where('part_type', '12')->sortBy('sn')->values();

        $totalSets = max($p15List->count(), $assemblies->count());
        $sets = [];
        $isTrayReturned = ($tray->status === 'returned' || $tray->session_status === 'COMPLETED_SHIFT' || !empty($tray->return_verified));

        for ($i = 0; $i < $totalSets; $i++) {
            $setNum = $i + 1;
            $a = $assyMap[$setNum] ?? null;

            if ($a && (!empty($a->finished_at) || $a->is_declined || in_array(strtoupper(trim((string)$a->status)), ['DECLINE', 'DECLINED']))) {
                $isDeclined = ($a->is_declined || in_array(strtoupper(trim((string)$a->status)), ['DECLINE', 'DECLINED'])) && $a->verified_status !== 'RESOLVED_RESTORED';
                $itemStatus = $isDeclined ? 'Declined' : ($a->status ?: 'Good');

                $sets[] = array_merge($a->toArray(), [
                    'id' => $a->id,
                    'set_number' => $setNum,
                    'part15_sn' => (!empty($a->part15_sn) && $a->part15_sn !== '-') ? $a->part15_sn : ($p15List->get($i)?->sn ?? '-'),
                    'part1_sn' => (!empty($a->part1_sn) && $a->part1_sn !== '-') ? $a->part1_sn : ($p1List->get($i)?->sn ?? '-'),
                    'part12_sn' => (!empty($a->part12_sn) && $a->part12_sn !== '-') ? $a->part12_sn : ($p12List->get($i)?->sn ?? '-'),
                    'status' => $itemStatus,
                    'is_declined' => $isDeclined,
                    'declined_reason' => $a->declined_reason,
                    'reason' => $a->reason ?: ($isDeclined ? $a->declined_reason : null),
                    'original_assembly_status' => $a->original_assembly_status ?: ($itemStatus === 'Dirakit Ulang' ? 'Good' : $itemStatus),
                    'assembly_status' => $a->original_assembly_status ?: ($itemStatus === 'Dirakit Ulang' ? 'Good' : $itemStatus),
                    'defective_parts' => $a->defective_parts,
                    'started_at' => $a->started_at,
                    'finished_at' => $a->finished_at,
                    'pic_assembly' => $a->pic_assembly,
                    'assy_doc_number' => $a->assy_doc_number,
                    'assy_test_status' => $a->assy_test_status,
                    'assy_test_checklist' => $a->assy_test_checklist,
                    'test_checklist' => $a->assy_test_checklist,
                    'assy_failure_reasons' => $a->assy_failure_reasons,
                    'assy_tested_at' => $a->assy_tested_at,
                    'assy_tester_username' => $a->assy_tester_username,
                    'assy_status_history' => $a->assy_status_history,
                    'qc_status' => $a->qc_status,
                    'qc_status_history' => $a->qc_status_history,
                    'qc_doc_number' => $a->qc_doc_number,
                    'qc_failure_reasons' => $a->qc_failure_reasons,
                    'warehouse_receipt_status' => $a->warehouse_receipt_status,
                    'warehouse_doc_number' => $a->warehouse_doc_number,
                ]);
            } else {
                $p15Val = $p15List->get($i)?->sn;
                $p1Val = $p1List->get($i)?->sn;
                $p12Val = $p12List->get($i)?->sn;

                $st15 = $p15Val ? \App\Models\MasterStock::where('sn', $p15Val)->value('status') : null;
                $isPartDeclined = ($st15 === 'Declined');
                $isPartReturned = $isTrayReturned || ($st15 === 'Tersedia');
                $defaultStatus = $isPartDeclined ? 'Declined' : ($isPartReturned ? 'Dikembalikan ke Gudang' : 'TERSUPPLY');

                $sets[] = [
                    'set_number' => $setNum,
                    'part15_sn' => $p15Val,
                    'part1_sn' => $p1Val,
                    'part12_sn' => $p12Val,
                    'status' => $defaultStatus,
                    'reason' => $isPartReturned ? 'Dikembalikan ke gudang oleh operator assembly' : 'Tray Tersupply ke Perakitan',
                    'is_declined' => $isPartDeclined,
                    'is_returned' => $isPartReturned,
                    'started_at' => ($inProgress && $inProgress->current_set == $setNum) ? $inProgress->started_at : null,
                    'finished_at' => null,
                    'pic_assembly' => null,
                    'assy_doc_number' => null,
                ];
            }
        }



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



            'sets' => $sets,



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



            'verified_by_user' => $this->resolveActor($request)['full_name'],



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



            $actorData = $this->resolveActor($request);
            $user = $actorData['full_name'];



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



        $result = $trays->map(function ($tray) {



            $assemblies = Assembly::where('tray_id', $tray->id)->get();



            $parts = DB::table('parts')->where('tray_id', $tray->id)->get();



            $p15Count = $parts->where('part_type', '15')->count();



            $assyCount = $assemblies->count();



            $totalSets = max($p15Count, $assyCount, 0);



            $finishedCount = $assemblies->filter(function ($a) {
                if ($a->is_declined || in_array(strtoupper(trim((string)$a->status)), ['DECLINE', 'DECLINED']) || $a->verified_status === 'DECLINED') {
                    return false;
                }
                return !empty($a->finished_at) && !empty($a->status);
            })->count();



            $returnedSetsCount = max(0, $totalSets - $finishedCount);



            if ($totalSets === 0) {



                $msCount = DB::table('master_stocks')->where('allocated_tray_id', $tray->id)->where('part_type', '15')->count();



                if ($msCount > 0) {



                    $totalSets = $msCount;



                    $returnedSetsCount = $msCount;



                }



            }



            // Auto-heal: jika seluruh sisa part dari tray ini sudah diproses (tidak ada yang masih Supplied)



            if (!$tray->return_verified) {



                $unresolvedSuppliedCount = DB::table('master_stocks')



                    ->where('allocated_tray_id', $tray->id)



                    ->whereIn('status', ['Supplied', 'HOLD_SUPPLIED'])



                    ->count();



                if ($unresolvedSuppliedCount === 0) {



                    $tray->update([



                        'return_verified' => true,



                        'return_verified_by' => $tray->return_verified_by ?: 'Gudang',



                        'return_verified_at' => $tray->return_verified_at ?: now(),



                        'status' => 'returned',



                    ]);



                    $tray->refresh();



                }



            }



            $trayArr = $tray->toArray();



            $trayArr['total_sets'] = $returnedSetsCount > 0 ? $returnedSetsCount : ($totalSets ?: 1);



            $trayArr['all_total_sets'] = $totalSets;



            $trayArr['finished_count'] = $finishedCount;



            $trayArr['uncompleted_count'] = $returnedSetsCount;



            return $trayArr;



        });



        return $this->successResponse($result);



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



        $p15List = $parts->where('part_type', '15')->sortBy('sn')->values();



        $p1List = $parts->where('part_type', '1')->sortBy('sn')->values();



        $p12List = $parts->where('part_type', '12')->sortBy('sn')->values();



        $numSets = max($p15List->count(), $assemblies->count());



        if ($numSets === 0) {



            $ms15 = DB::table('master_stocks')->where('allocated_tray_id', $tray->id)->where('part_type', '15')->get();



            $numSets = max(1, $ms15->count());



        }



        // Kumpulkan daftar set yang SUDAH SELESAI DIRAKIT (apapun kondisinya: Good, Hold, Scrap, dll.)



        // Set yang sudah selesai terakit HARUS TETAP TINGGAL di riwayat perakitan baki asal dan TIDAK BOLEH ikut dikembalikan ke gudang!



        $finishedAssemblies = $assemblies->filter(function ($a) {
            if ($a->is_declined || in_array(strtoupper(trim((string)($a->status ?? ''))), ['DECLINE', 'DECLINED']) || $a->verified_status === 'DECLINED') {
                return false;
            }
            return !empty($a->finished_at) || in_array(strtoupper(trim((string)($a->status ?? ''))), ['GOOD', 'HOLD', 'SCRAP', 'REWORK']);
        });



        $finishedSetNums = $finishedAssemblies->pluck('set_number')->toArray();



        $finishedCount = $finishedAssemblies->count();



        $sets = [];

        for ($i = 0; $i < $numSets; $i++) {
            $setNum = $i + 1;
            $assy = $assemblies->firstWhere('set_number', $setNum);

            $p15 = $assy?->part15_sn ?: ($p15List->get($i)?->sn ?? '-');
            $p1 = $assy?->part1_sn ?: ($p1List->get($i)?->sn ?? '-');
            $p12 = $assy?->part12_sn ?: ($p12List->get($i)?->sn ?? '-');

            $st15 = MasterStock::where('sn', $p15)->value('status');
            $st1 = MasterStock::where('sn', $p1)->value('status');
            $st12 = MasterStock::where('sn', $p12)->value('status');

            $isFinished = in_array($setNum, $finishedSetNums);
            $isDeclined = ($assy && ($assy->is_declined || in_array(strtoupper(trim((string)$assy->status)), ['DECLINE', 'DECLINED']) || $assy->verified_status === 'DECLINED')) || $st15 === 'Declined' || $st1 === 'Declined' || $st12 === 'Declined';
            $isVerified = !$isDeclined && ($st15 === 'Tersedia' || ($assy && in_array($assy->verified_status, ['ACC', 'RETURN_VERIFIED'])) || ($tray->return_verified && $st15 !== 'Supplied') || $isFinished);

            $statusText = $isDeclined ? 'Declined' : ($isVerified ? 'ACC' : 'Menunggu Verifikasi');

            $sets[] = [
                'set_number' => $setNum,
                'p15_sn' => $p15,
                'p1_sn' => $p1,
                'p12_sn' => $p12,
                'p15_status' => $st15,
                'p1_status' => $st1,
                'p12_status' => $st12,
                'status' => $statusText,
                'is_verified' => $isVerified,
                'is_declined' => $isDeclined,
                'declined_reason' => $assy?->declined_reason,
            ];
        }

        // Auto-heal / auto-complete: jika seluruh set pengembalian sudah diproses (semuanya is_verified atau is_declined)



        $isAllSetsResolved = count($sets) > 0 && collect($sets)->every(function ($s) {



            return !empty($s['is_verified']) || !empty($s['is_declined']) || in_array($s['status'], ['Tersedia', 'Declined']);



        });



        if ($isAllSetsResolved && !$tray->return_verified) {

            $actorData = $this->resolveActor($request);
            $user = $actorData['full_name'];

            $tray->update([
                'return_verified' => true,
                'return_verified_by' => $user,
                'return_verified_at' => now(),
                'status' => 'returned',
            ]);

            $tray->refresh();
        }

        if ($tray->return_verified) {
            // Auto-heal part stok pada tray yang telah diverifikasi kembali menjadi Tersedia
            $allTraySns = collect($sets)->flatMap(function ($s) {
                return array_filter([$s['p15_sn'], $s['p1_sn'], $s['p12_sn']]);
            })->filter(fn($sn) => $sn && $sn !== '-')->unique()->toArray();

            $declinedTraySns = collect($sets)->filter(fn($s) => !empty($s['is_declined']))->flatMap(function ($s) {
                return array_filter([$s['p15_sn'], $s['p1_sn'], $s['p12_sn']]);
            })->filter(fn($sn) => $sn && $sn !== '-')->unique()->toArray();

            $accTraySns = array_values(array_diff($allTraySns, $declinedTraySns));

            if (!empty($accTraySns)) {
                MasterStock::whereIn('sn', $accTraySns)
                    ->where('status', '!=', 'Declined')
                    ->where(function ($w) use ($tray) {
                        $w->where('allocated_tray_id', $tray->id)
                          ->orWhere('status', '!=', 'Tersedia');
                    })
                    ->update([
                        'status' => 'Tersedia',
                        'allocated_tray_id' => null,
                        'allocated_at' => null,
                        'used_at' => null,
                        'last_tray_id' => null,
                        'is_replaced_from_assy' => 0,
                        'last_reason' => "Pengembalian tray {$tray->tray_code} diverifikasi Gudang (Tersedia)",
                    ]);

                DB::table('parts')->where('tray_id', $tray->id)
                    ->whereIn('sn', $accTraySns)
                    ->update(['status' => 'Returned', 'updated_at' => now()]);

                // Perbarui status pada array sets agar langsung menampilkan Tersedia
                foreach ($sets as &$s) {
                    if (in_array($s['p15_sn'], $accTraySns)) $s['p15_status'] = 'Tersedia';
                    if (in_array($s['p1_sn'], $accTraySns)) $s['p1_status'] = 'Tersedia';
                    if (in_array($s['p12_sn'], $accTraySns)) $s['p12_status'] = 'Tersedia';
                }
                unset($s);
            }
        }



        $trayArr = $tray->toArray();



        $trayArr['finished_count'] = $finishedCount;



        $trayArr['total_sets'] = count($sets);



        return response()->json([



            'ok' => true,



            'tray' => $trayArr,



            'finished_count' => $finishedCount,



            'sets' => $sets,



        ]);



    }



    /**



     * Verifikasi Seluruh Part Pengembalian Tray (Verifikasi Semua)



     * Endpoint: POST /api/gudang/verify-return-tray



     */



    public function verifyReturnTray(Request $request)



    {



        $trayId = $request->input('tray_id');



        $trayCode = $request->input('tray_code');



        $actorData = $this->resolveActor($request);
        $user = $actorData['full_name'];



        $tray = Tray::where(function ($w) use ($trayId, $trayCode) {



            if ($trayId) $w->where('id', $trayId);



            if ($trayCode) $w->orWhere('tray_code', $trayCode);



        })->first();



        if (!$tray) {



            return $this->errorResponse('Tray tidak ditemukan', 404);



        }



        return DB::transaction(function () use ($tray, $user) {

            // 1. Dapatkan serial number parts dari SEMUA sumber terkait tray ini (parts, master_stocks, assemblies)
            $parts = DB::table('parts')->where('tray_id', $tray->id)->get();
            $sns = $parts->pluck('sn')->filter()->unique()->toArray();
            $msSns = DB::table('master_stocks')
                ->where('allocated_tray_id', $tray->id)
                ->pluck('sn')
                ->filter()
                ->unique()
                ->toArray();
            $assySns = DB::table('assemblies')
                ->where('tray_id', $tray->id)
                ->get()
                ->flatMap(function ($a) {
                    return array_filter([$a->part15_sn, $a->part1_sn, $a->part12_sn]);
                })
                ->filter()
                ->unique()
                ->toArray();
            $allSns = array_unique(array_merge($sns, $msSns, $assySns));

            // Dapatkan serial number yang declined (tidak dikembalikan ke Tersedia)
            $declinedSns = Assembly::where('tray_id', $tray->id)
                ->where(function ($w) {
                    $w->where('is_declined', true)
                      ->orWhereIn('status', ['Decline', 'DECLINED'])
                      ->orWhere('verified_status', 'DECLINED');
                })
                ->get()
                ->flatMap(function ($a) {
                    return array_filter([$a->part15_sn, $a->part1_sn, $a->part12_sn]);
                })->unique()->filter()->toArray();

            $declinedMsSns = DB::table('master_stocks')
                ->where(function ($w) use ($tray, $allSns) {
                    $w->where('allocated_tray_id', $tray->id)
                      ->orWhereIn('sn', $allSns);
                })
                ->where('status', 'Declined')
                ->pluck('sn')->filter()->unique()->toArray();

            $allDeclinedSns = array_unique(array_merge($declinedSns, $declinedMsSns));

            // Saring serial number yang dikembalikan ke stok gudang: SEMUA part pada tray ini KECUALI yang di-decline
            $returnedSns = array_values(array_diff($allSns, $allDeclinedSns));

            if (!empty($returnedSns)) {
                MasterStock::whereIn('sn', $returnedSns)
                    ->where('status', '!=', 'Declined')
                    ->update([
                        'status' => 'Tersedia',
                        'allocated_tray_id' => null,
                        'allocated_at' => null,
                        'used_at' => null,
                        'last_tray_id' => null,
                        'is_replaced_from_assy' => 0,
                        'last_reason' => "Pengembalian tray {$tray->tray_code} diverifikasi Gudang (Tersedia)",
                    ]);

                foreach ($returnedSns as $sn) {
                    $p = DB::table('master_stocks')->where('sn', $sn)->first();
                    PartAuditLog::create([
                        'part_type' => $p?->part_type ?: '15',
                        'sn' => $sn,
                        'old_status' => 'Supplied/Return',
                        'new_status' => 'Tersedia',
                        'action_type' => 'RETURN_VERIFY_TRAY_ALL',
                        'actor_username' => $user,
                        'actor_role' => 'gudang',
                        'reason' => "Verifikasi pengembalian part untuk tray {$tray->tray_code}",
                    ]);
                }
            }

            // Update status parts yang dikembalikan ke gudang di tabel parts menjadi Returned (jangan dihapus agar riwayat dan SN tetap utuh)
            if (!empty($returnedSns)) {
                DB::table('parts')->where('tray_id', $tray->id)
                    ->whereIn('sn', $returnedSns)
                    ->update(['status' => 'Returned', 'updated_at' => now()]);
            }

            // Update assembly pada baki ini menjadi ACC / terverifikasi
            Assembly::where('tray_id', $tray->id)
                ->where('is_declined', false)
                ->whereNotIn('status', ['Decline', 'DECLINED'])
                ->update([
                    'verified_status' => 'ACC',
                    'warehouse_receipt_status' => 'ACCEPTED',
                ]);

            $tray->update([
                'return_verified' => true,
                'return_verified_by' => $user,
                'return_verified_at' => now(),
                'status' => 'returned',
            ]);



            return response()->json([



                'ok' => true,



                'message' => "Pengembalian part sisa untuk Tray {$tray->tray_code} berhasil diverifikasi. Part yang belum dirakit otomatis kembali Tersedia di Master Stok."



            ]);



        });



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
        if (strtolower($newStatus) === 'rework') {
            $newStatus = 'Hold';
        }



        $reason = $request->input('reason', '');



        $actorData = $this->resolveActor($request);
        $user = $actorData['full_name'];



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



        $actorData = $this->resolveActor($request);
        $user = $actorData['full_name'];



        $assembly = Assembly::where('tray_id', $trayId)->where('set_number', $setNum)->first();

        return DB::transaction(function () use ($trayId, $setNum, $p15Sn, $p1Sn, $p12Sn, $user, $assembly) {



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



            // Perbarui status part di tabel parts menjadi Returned (JANGAN DIHAPUS agar history dan data SN tidak hilang)
            DB::table('parts')->where('tray_id', $trayId)
                ->whereIn('sn', array_filter([$p15Sn, $p1Sn, $p12Sn]))
                ->update(['status' => 'Returned', 'updated_at' => now()]);

            // Update status verifikasi assembly pada baki ini jika ada
            if ($assembly) {
                $assembly->update([
                    'verified_status' => 'ACC',
                    'warehouse_receipt_status' => 'ACCEPTED',
                ]);
            }

            $tray = Tray::find($trayId);
            $unreturnedCount = MasterStock::where('allocated_tray_id', $trayId)
                ->where('status', '!=', 'Declined')
                ->count();

            $isFullyVerified = ($unreturnedCount === 0);



            if ($isFullyVerified && $tray) {



                $tray->update([



                    'return_verified' => true,



                    'return_verified_by' => $user,



                    'return_verified_at' => now(),



                    'status' => 'returned',



                ]);



            }



            return response()->json([



                'ok' => true,



                'message' => "Set #{$setNum} berhasil diverifikasi dan dikembalikan ke stok gudang.",



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



        $actorData = $this->resolveActor($request);
        $user = $actorData['full_name'];



        $assembly = Assembly::where('tray_id', $trayId)->where('set_number', $setNum)->first();



        if ($assembly && !$assembly->is_declined && !in_array(strtoupper(trim((string)$assembly->status)), ['DECLINE', 'DECLINED']) && (!empty($assembly->finished_at) || in_array(strtoupper(trim((string)$assembly->status)), ['GOOD', 'HOLD', 'SCRAP', 'REWORK']))) {



            return response()->json([



                'ok' => false,



                'error' => "Set #{$setNum} telah selesai dirakit ({$assembly->status}) dan tidak dapat didecline sebagai barang hilang."



            ], 400);



        }



        return DB::transaction(function () use ($trayId, $setNum, $declinedParts, $p15Sn, $p1Sn, $p12Sn, $reason, $user) {
            $tray = Tray::find($trayId);
            $assembly = Assembly::where('tray_id', $trayId)
                ->where('set_number', $setNum)
                ->first();

            if ($assembly) {
                $assembly->update([
                    'is_declined' => true,
                    'declined_reason' => $reason,
                    'status' => 'Decline',
                    'verified_status' => 'DECLINED',
                    'finished_at' => null,
                    'part15_sn' => ($p15Sn && $p15Sn !== '-') ? $p15Sn : $assembly->part15_sn,
                    'part1_sn' => ($p1Sn && $p1Sn !== '-') ? $p1Sn : $assembly->part1_sn,
                    'part12_sn' => ($p12Sn && $p12Sn !== '-') ? $p12Sn : $assembly->part12_sn,
                ]);
            } else {
                $assembly = Assembly::create([
                    'tray_id' => $trayId,
                    'tray_code' => $tray ? $tray->tray_code : null,
                    'set_number' => $setNum,
                    'part15_sn' => ($p15Sn && $p15Sn !== '-') ? $p15Sn : null,
                    'part1_sn' => ($p1Sn && $p1Sn !== '-') ? $p1Sn : null,
                    'part12_sn' => ($p12Sn && $p12Sn !== '-') ? $p12Sn : null,
                    'is_declined' => true,
                    'declined_reason' => $reason,
                    'status' => 'Decline',
                    'verified_status' => 'DECLINED',
                    'pic_assembly' => $tray ? ($tray->pic_assembly ?: ($user->username ?? null)) : ($user->username ?? null),
                    'started_at' => null,
                    'finished_at' => null,
                ]);
            }



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



                            'status' => 'Declined',



                            'allocated_tray_id' => null,



                            'allocated_at' => null,



                            'used_at' => null,



                            'last_tray_id' => null,



                            'is_replaced_from_assy' => 0,



                            'last_reason' => $reason,



                        ]);



                    PartAuditLog::create([



                        'part_type' => $pType,



                        'sn' => $sn,



                        'old_status' => 'Supplied/Return',



                        'new_status' => 'Declined',



                        'action_type' => 'RETURN_DECLINE_SET',



                        'actor_username' => $user,



                        'actor_role' => 'gudang',



                        'reason' => $reason,



                    ]);



                }



            }



            // Update status part di tabel parts menjadi Declined (jangan dihapus)

            DB::table('parts')->where('tray_id', $trayId)

                ->whereIn('sn', array_filter([$p15Sn, $p1Sn, $p12Sn]))

                ->update(['status' => 'Declined', 'updated_at' => now()]);



            // Periksa apakah seluruh sisa part dari tray ini sudah selesai diverifikasi atau didecline



            $tray = Tray::find($trayId);



            $finishedAssemblies = Assembly::where('tray_id', $trayId)



                ->where(function ($w) {



                    $w->whereNotNull('finished_at')



                      ->orWhereIn('status', ['Good', 'Hold', 'Scrap', 'REWORK']);



                })->get();



            $finishedSns = $finishedAssemblies->flatMap(function ($a) {



                return array_filter([$a->part15_sn, $a->part1_sn, $a->part12_sn]);



            })->unique()->filter()->toArray();



            $unreturnedCount = MasterStock::where('allocated_tray_id', $trayId)



                ->whereNotIn('sn', $finishedSns)



                ->whereIn('status', ['Supplied', 'HOLD_SUPPLIED'])



                ->count();



            $isFullyVerified = ($unreturnedCount === 0);



            if ($isFullyVerified && $tray) {



                $tray->update([



                    'return_verified' => true,



                    'return_verified_by' => $user,



                    'return_verified_at' => now(),



                    'status' => 'returned',



                ]);



            }



            return response()->json([



                'ok' => true,



                'message' => "Set #{$setNum} berhasil di-decline dan diteruskan ke verifikasi Admin.",



                'is_fully_verified' => $isFullyVerified



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




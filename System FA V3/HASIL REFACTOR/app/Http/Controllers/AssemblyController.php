<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Assembly;
use App\Models\Tray;
use App\Models\MasterStock;
use App\Models\AssemblyDowntime;
use App\Models\AssemblyWoHistory;

class AssemblyController extends Controller
{
    /**
     * Memulai Pengerjaan Perakitan Set (Start Timer)
     */
    public function startSet(Request $request)
    {
        $trayId = $request->input('tray_id');
        $setNumber = (int)$request->input('set_number');
        $operator = auth()->user()?->username ?: $request->input('operator', 'assembly');

        $assembly = Assembly::firstOrNew([
            'tray_id' => $trayId,
            'set_number' => $setNumber,
        ]);

        if (!$assembly->started_at) {
            $assembly->started_at = now();
            $assembly->pic_assembly = $operator;
            $assembly->save();
        }

        return $this->successResponse($assembly, "Pengerjaan Set #{$setNumber} dimulai.");
    }

    /**
     * Menyimpan Hasil Perakitan Set
     */
    public function saveSet(Request $request)
    {
        $request->validate([
            'tray_id' => 'required|integer',
            'set_number' => 'required|integer',
            'part15_sn' => 'required|string',
            'part12_sn' => 'required|string',
            'part1_sn' => 'required|string',
            'status' => 'required|string', // Good, Hold, Scrap, Rework
        ]);

        return DB::transaction(function () use ($request) {
            $tray = Tray::findOrFail($request->tray_id);
            if ($tray->status !== 'active') {
                return response()->json(['ok' => false, 'error' => "Tray {$tray->tray_code} sudah tidak aktif (status: {$tray->status}) dan tidak dapat dimodifikasi."], 400);
            }

            $p15 = trim($request->part15_sn);
            $p1 = trim($request->part1_sn);
            $p12 = trim($request->part12_sn);

            // 1. Anti-duplikasi di tray yang sama (slot lain)
            $dupSameTray = Assembly::where('tray_id', $request->tray_id)
                ->where('set_number', '!=', $request->set_number)
                ->where(function ($w) use ($p15, $p1, $p12) {
                    $w->where('part15_sn', $p15)
                      ->orWhere('part1_sn', $p1)
                      ->orWhere('part12_sn', $p12);
                })
                ->where(function ($w) {
                    $w->whereNull('is_reregistered')->orWhere('is_reregistered', 0);
                })
                ->first();

            if ($dupSameTray) {
                return response()->json([
                    'ok' => false, 
                    'error' => "Salah satu nomor seri part sudah digunakan di Set #{$dupSameTray->set_number} pada baki ini! Duplikasi data dilarang."
                ], 400);
            }

            // 2. Validasi kepemilikan part: Part wajib terdaftar di baki ini
            foreach ([['15', $p15], ['1', $p1], ['12', $p12]] as [$pType, $sn]) {
                $existsInTray = DB::table('parts')
                    ->where('tray_id', $request->tray_id)
                    ->where('part_type', $pType)
                    ->where('sn', $sn)
                    ->exists();
                if (!$existsInTray) {
                    return response()->json([
                        'ok' => false, 
                        'error' => "Part {$pType} SN {$sn} tidak terdaftar pada alokasi baki {$tray->tray_code}!"
                    ], 400);
                }
            }

            $assembly = Assembly::updateOrCreate(
                [
                    'tray_id' => $request->tray_id,
                    'set_number' => $request->set_number,
                ],
                [
                    'pic_assembly' => auth()->user()?->username ?: $request->input('pic_assembly', 'assembly'),
                    'part15_sn' => $p15,
                    'part12_sn' => $p12,
                    'part1_sn' => $p1,
                    'status' => $request->status,
                    'reason' => $request->input('reason'),
                    'defective_parts' => $request->input('defective_parts'),
                    'started_at' => $request->input('started_at') ?: now(),
                    'finished_at' => now(),
                ]
            );

            // Update status master stocks jika Done/Good
            $stockStatus = ($request->status === 'Good') ? 'Done' : $request->status;
            foreach ([$p15, $p1, $p12] as $sn) {
                MasterStock::where('sn', $sn)->update([
                    'status' => $stockStatus,
                    'used_at' => now(),
                ]);
            }

            // Catat ke WO History
            AssemblyWoHistory::create([
                'tray_id' => $assembly->tray_id,
                'tray_code' => $tray->tray_code,
                'set_number' => $assembly->set_number,
                'pic_assembly' => $assembly->pic_assembly,
                'part15_sn' => $assembly->part15_sn,
                'part12_sn' => $assembly->part12_sn,
                'part1_sn' => $assembly->part1_sn,
                'status' => $assembly->status,
                'reason' => $assembly->reason,
                'defective_parts' => $assembly->defective_parts,
                'started_at' => $assembly->started_at,
                'finished_at' => $assembly->finished_at,
                'source' => 'ASSEMBLY',
            ]);

            return $this->successResponse($assembly, "Set #{$assembly->set_number} berhasil disimpan.");
        });
    }

    /**
     * Submit Hasil Tes Fungsional 1 (TF 1) - Menjalankan 17 Parameter Checklist
     */
    public function submitTest(Request $request)
    {
        $request->validate([
            'tray_id' => 'required|integer',
            'set_number' => 'required|integer',
            'test_checklist' => 'required|array', // 17 checklist items
        ]);

        $assembly = Assembly::where('tray_id', $request->tray_id)
            ->where('set_number', $request->set_number)
            ->firstOrFail();

        // VALIDASI ALUR KERJA: Set harus sudah selesai dirakit
        if (empty($assembly->finished_at) || $assembly->status !== 'Good') {
            return response()->json([
                'ok' => false, 
                'error' => "Set #{$assembly->set_number} belum selesai dirakit atau statusnya bukan 'Good'! Unit tidak dapat diuji TF 1."
            ], 400);
        }

        $checklist = $request->test_checklist;
        $hasFailed = collect($checklist)->contains(fn ($t) => in_array(strtoupper($t['status'] ?? ''), ['NOT_GOOD', 'HOLD', 'FAIL', 'FAILED']));
        $testStatus = $hasFailed ? 'Hold TF 1' : 'Good';

        $docNumber = $request->input('assy_doc_number');
        if (!$hasFailed && empty($docNumber)) {
            $tray = Tray::find($assembly->tray_id);
            $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));
            if ($isPU) {
                preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);
                $puNum = !empty($m[1]) ? (int)$m[1] : 1;
                $docNumber = "ASSY/QC/PU/{$puNum}";
            } else {
                $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                $docNumber = "ASSY/QC/{$docSeq}";
            }
        }

        $assembly->update([
            'test_checklist' => $checklist,
            'assy_test_checklist' => json_encode($checklist),
            'assy_test_status' => $testStatus,
            'notes' => $request->input('notes'),
            'assy_tester_username' => auth()->user()?->username ?: 'tester',
            'assy_tested_at' => now(),
            'assy_doc_number' => $docNumber,
            // Isolasi TF 1: Catat kendala TF 1 tanpa mengubah status perakitan yang sudah Good
            'assy_failure_reasons' => $hasFailed ? ($request->input('notes') ?: 'Kendala pada uji fungsional 1 (TF 1)') : null,
        ]);

        return $this->successResponse($assembly, "Hasil Uji Fungsional 1 (TF 1) Set #{$assembly->set_number} tersimpan ({$testStatus}).");
    }

    /**
     * Jeda / Istirahat Workstation (Downtime Tracker)
     */
    public function pause(Request $request)
    {
        $request->validate([
            'tray_code' => 'required|string',
            'reason' => 'required|string',
        ]);

        $downtime = AssemblyDowntime::create([
            'tray_id' => $request->input('tray_id'),
            'tray_code' => $request->tray_code,
            'set_number' => $request->input('set_number'),
            'operator' => auth()->user()?->username ?: $request->input('operator', 'operator'),
            'reason' => $request->reason,
            'paused_at' => now(),
        ]);

        return $this->successResponse($downtime, 'Sesi pengerjaan dijeda.');
    }

    /**
     * Lanjut Pengerjaan dari Jeda (Resume Workstation)
     */
    public function resume(Request $request)
    {
        $trayCode = $request->input('tray_code');
        $operator = auth()->user()?->username ?: $request->input('operator');

        $downtime = AssemblyDowntime::where('tray_code', $trayCode)
            ->whereNull('resumed_at')
            ->latest()
            ->first();

        if ($downtime) {
            $resumedAt = now();
            $duration = $downtime->paused_at->diffInSeconds($resumedAt);
            $downtime->update([
                'resumed_at' => $resumedAt,
                'duration_seconds' => $duration,
            ]);
        }

        return $this->successResponse($downtime, 'Sesi perakitan dilanjutkan.');
    }

    /**
     * Mengambil item-item yang menunggu rakit ulang (Waiting Reassembly)
     */
    public function pendingReassembly()
    {
        $items = Assembly::with('tray')
            ->where('verified_status', 'WAITING_REASSEMBLY')
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse($items);
    }

    // Aliases to match routes
    public function saveAssembly(Request $request) { return $this->saveSet($request); }
    public function getPendingReassembly() { return $this->pendingReassembly(); }

    /**
     * Ganti Part Perakitan (Replace Part) dengan Anti-Duplikasi
     */
    public function replacePart(Request $request)
    {
        $request->validate([
            'tray_id' => 'required|integer',
            'assembly_id' => 'required|integer',
            'part_type' => 'required|string',
            'new_sn' => 'required|string',
        ]);

        $trayId = $request->tray_id;
        $assemblyId = $request->assembly_id;
        $partType = $request->part_type;
        $newSn = trim($request->new_sn);
        $reason = $request->input('reason', 'Penggantian part');
        $operator = auth()->user()?->username ?: $request->input('operator', 'assembly');

        return DB::transaction(function () use ($trayId, $assemblyId, $partType, $newSn, $reason, $operator) {
            $assy = Assembly::where('id', $assemblyId)->where('tray_id', $trayId)->firstOrFail();

            // Cek ketersediaan part baru di Master Stok
            $newStock = MasterStock::where('part_type', $partType)
                ->where('sn', $newSn)
                ->first();

            if (!$newStock || $newStock->status !== 'Tersedia') {
                return response()->json([
                    'ok' => false,
                    'error' => "Part baru {$partType} SN {$newSn} tidak ditemukan atau statusnya bukan 'Tersedia' di Master Stok!"
                ], 400);
            }

            // Validasi: pastikan part baru tidak sedang aktif digunakan di set lain pada tray yang sama
            $activeInSame = Assembly::where('tray_id', $trayId)
                ->where('id', '!=', $assemblyId)
                ->where(function ($w) use ($newSn, $partType) {
                    if ($partType === '15') $w->where('part15_sn', $newSn);
                    elseif ($partType === '1') $w->where('part1_sn', $newSn);
                    elseif ($partType === '12') $w->where('part12_sn', $newSn);
                })
                ->where(function ($w) {
                    $w->whereNull('is_reregistered')->orWhere('is_reregistered', 0);
                })
                ->first();

            if ($activeInSame) {
                return response()->json([
                    'ok' => false,
                    'error' => "Part {$partType} SN {$newSn} sedang aktif digunakan di Set #{$activeInSame->set_number} pada tray ini!"
                ], 400);
            }

            // Ambil SN lama
            $oldSn = match ($partType) {
                '15' => $assy->part15_sn,
                '1' => $assy->part1_sn,
                '12' => $assy->part12_sn,
                default => null,
            };

            // Lepaskan dari set lain di tray ini jika ada (misal bekas PU yang sudah dilepas)
            if ($partType === '15') {
                Assembly::where('tray_id', $trayId)->where('part15_sn', $newSn)->where('id', '!=', $assemblyId)->update(['part15_sn' => '-']);
                $assy->part15_sn = $newSn;
            } elseif ($partType === '1') {
                Assembly::where('tray_id', $trayId)->where('part1_sn', $newSn)->where('id', '!=', $assemblyId)->update(['part1_sn' => '-']);
                $assy->part1_sn = $newSn;
            } elseif ($partType === '12') {
                Assembly::where('tray_id', $trayId)->where('part12_sn', $newSn)->where('id', '!=', $assemblyId)->update(['part12_sn' => '-']);
                $assy->part12_sn = $newSn;
            }
            $assy->save();

            // Bersihkan dulu jika newSn sempat tersisa di tray ini agar tidak bentrok UNIQUE constraint
            DB::table('parts')->where('tray_id', $trayId)->where('part_type', $partType)->where('sn', $newSn)->delete();
            $updated = DB::table('parts')->where('tray_id', $trayId)->where('part_type', $partType)->where('sn', $oldSn)->update(['sn' => $newSn]);
            if ($updated === 0) {
                DB::table('parts')->insertOrIgnore([
                    'tray_id' => $trayId,
                    'part_type' => $partType,
                    'sn' => $newSn,
                    'status' => 'Supplied',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update master stock part baru
            $newStock->update([
                'status' => 'Supplied',
                'allocated_tray_id' => $trayId,
                'allocated_at' => now(),
            ]);

            // Update master stock part lama: Masuk Master Stock status Hold dengan catatan part diganti
            if ($oldSn && $oldSn !== '-') {
                MasterStock::where('sn', $oldSn)->where('part_type', $partType)->update([
                    'status' => 'Hold',
                    'allocated_tray_id' => null,
                    'allocated_at' => null,
                    'used_at' => null,
                    'last_tray_id' => null,
                    'is_replaced_from_assy' => 1,
                    'last_reason' => "Part yang diganti di Set #{$assy->set_number} (Diganti SN {$newSn}): {$reason}",
                    'last_operator' => $operator,
                ]);

                // Audit log
                DB::table('part_audit_logs')->insert([
                    'part_type' => $partType,
                    'sn' => $oldSn,
                    'old_status' => 'Supplied',
                    'new_status' => 'Hold',
                    'action_type' => 'REPLACE_PART',
                    'actor_username' => $operator,
                    'actor_role' => auth()->user()?->role ?: 'operator',
                    'reason' => "Part yang diganti dengan SN {$newSn}: {$reason}",
                    'created_at' => now(),
                ]);

                // Masuk ke antrean Verifikasi Pergantian Part (Admin)
                DB::table('part_replacement_requests')->insert([
                    'assembly_id' => $assemblyId,
                    'tray_id' => $trayId,
                    'set_number' => $assy->set_number,
                    'replacements_json' => json_encode([
                        [
                            'part_type' => $partType,
                            'old_sn' => $oldSn,
                            'new_sn' => $newSn,
                        ]
                    ]),
                    'old_part_status' => 'Hold',
                    'reason' => $reason,
                    'requestor_username' => $operator,
                    'status' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $assy->update(['verified_status' => 'PENDING_REPLACEMENT_VERIF']);
            }

            return $this->successResponse($assy, "Part {$partType} SN {$oldSn} berhasil diajukan ganti dengan SN {$newSn} (Menunggu Verifikasi Admin).");
        });
    }

    /**
     * Dispatch Reassembly (Kirim Set Perakitan Ulang ke Tray Baru)
     */
    public function dispatchReassembly(Request $request)
    {
        $request->validate([
            'tray_id' => 'required|integer',
            'assembly_ids' => 'required|array',
        ]);

        $newTrayId = $request->tray_id;
        $assemblyIds = $request->assembly_ids;

        return DB::transaction(function () use ($newTrayId, $assemblyIds) {
            $items = Assembly::whereIn('id', $assemblyIds)->get();

            foreach ($items as $item) {
                // Pindahkan SN ke tray baru dan bersihkan dari tray lama
                foreach ([['15', $item->part15_sn], ['1', $item->part1_sn], ['12', $item->part12_sn]] as [$pType, $sn]) {
                    if ($sn && $sn !== '-') {
                        MasterStock::where('part_type', $pType)->where('sn', $sn)->update([
                            'status' => 'Supplied',
                            'allocated_tray_id' => $newTrayId,
                            'allocated_at' => now(),
                        ]);

                        // Hapus dari tabel parts tray asal agar tidak nyangkut/duplikat
                        if ($item->tray_id) {
                            DB::table('parts')->where('tray_id', $item->tray_id)->where('part_type', $pType)->where('sn', $sn)->delete();
                        }
                    }
                }

                $item->update([
                    'is_reregistered' => 1,
                    'verified_status' => 'REASSEMBLED',
                ]);
            }

            return $this->successResponse(null, count($items) . " set berhasil ditugaskan untuk perakitan ulang.");
        });
    }

    /**
     * Riwayat Audit Downtime Workstation
     */
    public function getDowntimeLogs(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $operator = $request->input('operator');
        $trayCode = $request->input('tray_code');
        $q = $request->input('q');

        $query = AssemblyDowntime::orderBy('paused_at', 'desc');

        if ($startDate) {
            $query->whereDate('paused_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('paused_at', '<=', $endDate);
        }
        if ($operator && $operator !== 'ALL' && $operator !== 'Semua Operator') {
            $query->where('operator', 'like', "%{$operator}%");
        }
        if ($trayCode) {
            $query->where('tray_code', 'like', "%{$trayCode}%");
        }
        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('reason', 'like', "%{$q}%")
                  ->orWhere('operator', 'like', "%{$q}%")
                  ->orWhere('tray_code', 'like', "%{$q}%");
            });
        }

        $logs = $query->limit(1000)->get();
        return $this->successResponse($logs);
    }

    public function getProductionProducts()
    {
        $products = DB::table('trays')
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->pluck('product_type')
            ->toArray();

        if (empty($products) || !in_array('Nanggala P9', $products)) {
            array_unshift($products, 'Nanggala P9');
        }

        return response()->json(['ok' => true, 'data' => $products]);
    }

    public function getAssemblyResults(Request $request)
    {
        $q = trim($request->input('q', ''));
        $productType = trim($request->input('product_type', ''));
        $statusFilter = strtoupper(trim($request->input('status', '')));
        $trayCode = trim($request->input('tray_code', ''));
        $operator = trim($request->input('operator', ''));
        $docNumber = trim($request->input('assy_doc_number', '')) ?: trim($request->input('doc_number', ''));

        $query = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->leftJoin('users as u_assy', function ($join) {
                $join->on('a.pic_assembly', '=', 'u_assy.username')
                     ->orOn('a.pic_assembly', '=', 'u_assy.full_name');
            })
            ->select([
                't.id as tray_id',
                't.tray_code',
                't.source_document_mo',
                't.product_type',
                'a.id as assembly_id',
                'a.set_number',
                'a.part15_sn',
                'a.part12_sn',
                'a.part1_sn',
                DB::raw("COALESCE(a.status, 'Good') as status"),
                'a.reason',
                'a.defective_parts',
                'a.rework_reason',
                'a.verified_status',
                'a.is_declined',
                'a.declined_reason',
                'a.started_at',
                'a.finished_at',
                'a.assy_doc_number',
                'a.qc_doc_number',
                'a.qc_status',
                'a.qc_failure_reasons',
                'a.qc_notes',
                'a.lifecycle_path',
                'a.warehouse_receipt_status',
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly"),
                't.created_at as tray_created_at'
            ]);

        if ($productType && $productType !== 'ALL') {
            $query->where('t.product_type', $productType);
        }
        if ($trayCode) {
            $query->where('t.tray_code', 'like', "%{$trayCode}%");
        }
        if ($operator && $operator !== 'ALL' && $operator !== 'Semua Operator') {
            $query->where(function ($w) use ($operator) {
                $w->where('a.pic_assembly', $operator)
                  ->orWhere('t.assigned_operator', $operator)
                  ->orWhere('u_assy.full_name', $operator)
                  ->orWhere('u_assy.username', $operator);
            });
        }
        if ($docNumber && $docNumber !== 'ALL' && $docNumber !== 'Semua Dokumen') {
            $query->where(function ($w) use ($docNumber) {
                $w->where('t.tray_code', $docNumber)
                  ->orWhere('a.assy_doc_number', $docNumber);
            });
        }
        if ($statusFilter && $statusFilter !== 'ALL') {
            $query->whereRaw("UPPER(COALESCE(a.status, 'Good')) = ?", [$statusFilter]);
        }
        if ($q) {
            $query->where(function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where('a.part15_sn', 'like', $like)
                  ->orWhere('a.part1_sn', 'like', $like)
                  ->orWhere('a.part12_sn', 'like', $like)
                  ->orWhere('t.tray_code', 'like', $like)
                  ->orWhere('t.source_document_mo', 'like', $like)
                  ->orWhere('a.reason', 'like', $like);
            });
        }

        $results = $query->orderBy('a.id', 'desc')->limit(1000)->get();
        return response()->json(['ok' => true, 'data' => $results]);
    }

    /**
     * Tindakan Sesi Perakitan (Kembalikan ke Gudang atau Serah Terima Shift)
     * Endpoint: POST /api/assembly/session-action
     */
    public function sessionAction(Request $request)
    {
        $trayId = $request->input('tray_id');
        $trayCode = $request->input('tray_code');
        $action = $request->input('action', 'RETURN_WAREHOUSE');
        $reason = $request->input('reason', '');
        $newOperator = $request->input('new_operator', '');

        $tray = Tray::where(function ($w) use ($trayId, $trayCode) {
            if ($trayId) $w->where('id', $trayId);
            if ($trayCode) $w->orWhere('tray_code', $trayCode);
        })->first();

        if (!$tray) {
            return response()->json(['ok' => false, 'error' => 'Tray tidak ditemukan.'], 404);
        }

        if ($action === 'RETURN_WAREHOUSE') {
            $tray->update([
                'session_status' => 'COMPLETED_SHIFT',
                'status' => 'returned',
            ]);
            return response()->json([
                'ok' => true,
                'message' => "Tray {$tray->tray_code} berhasil dipindahkan/dikembalikan ke Gudang!"
            ]);
        } elseif ($action === 'HANDOVER_SHIFT') {
            if (!$newOperator) {
                return response()->json(['ok' => false, 'error' => 'Operator penerus belum dipilih.'], 400);
            }

            return DB::transaction(function () use ($tray, $newOperator) {
                // Cari set yang SUDAH selesai dirakit pada tray ini
                $finishedSets = Assembly::where('tray_id', $tray->id)
                    ->whereNotNull('finished_at')
                    ->get();
                
                $finishedSns = $finishedSets->flatMap(function ($a) {
                    return array_filter([$a->part15_sn, $a->part1_sn, $a->part12_sn]);
                })->toArray();

                // Cari seluruh parts di baki ini yang BELUM selesai dirakit
                $unfinishedParts = DB::table('parts')
                    ->where('tray_id', $tray->id)
                    ->whereNotIn('sn', $finishedSns)
                    ->get();

                // Jika seluruh set sudah selesai dirakit, cukup serahkan baki
                if ($unfinishedParts->isEmpty()) {
                    $tray->update([
                        'assigned_operator' => $newOperator,
                        'session_status' => 'HANDED_OVER',
                    ]);
                    return response()->json([
                        'ok' => true,
                        'message' => "Seluruh set pada baki {$tray->tray_code} telah selesai dirakit. Baki diserah-terimakan ke operator {$newOperator}.",
                        'new_tray_code' => $tray->tray_code,
                    ]);
                }

                // Buat nomor baki baru kelanjutan (-S2, -S3, dst)
                if (preg_match('/-S(\d+)$/', $tray->tray_code, $m)) {
                    $nextS = (int)$m[1] + 1;
                    $newTrayCode = preg_replace('/-S\d+$/', "-S{$nextS}", $tray->tray_code);
                } else {
                    $newTrayCode = $tray->tray_code . '-S2';
                }

                $candidateCode = $newTrayCode;
                $counter = 2;
                while (Tray::where('tray_code', $candidateCode)->exists()) {
                    $counter++;
                    $candidateCode = preg_replace('/-S\d+$/', '', $tray->tray_code) . "-S{$counter}";
                }
                $newTrayCode = $candidateCode;

                // Buat tray baru untuk pengerjaan shift lanjutan (-S2)
                $newTray = Tray::create([
                    'tray_code' => $newTrayCode,
                    'pic_warehouse' => $tray->pic_warehouse,
                    'assigned_operator' => $newOperator,
                    'source_document_mo' => $tray->source_document_mo,
                    'product_type' => $tray->product_type,
                    'status' => 'active',
                    'session_status' => 'active',
                ]);

                // Pindahkan set yang belum selesai dirakit di tabel assemblies ke newTray
                Assembly::where('tray_id', $tray->id)
                    ->whereNull('finished_at')
                    ->update([
                        'tray_id' => $newTray->id,
                        'pic_assembly' => $newOperator,
                    ]);

                // Pindahkan sisa parts yang belum selesai ke newTray
                $unfinishedSns = $unfinishedParts->pluck('sn')->toArray();
                DB::table('parts')
                    ->where('tray_id', $tray->id)
                    ->whereIn('sn', $unfinishedSns)
                    ->update(['tray_id' => $newTray->id]);

                // Update master_stocks allocated_tray_id untuk part yang belum selesai
                MasterStock::whereIn('sn', $unfinishedSns)->update([
                    'allocated_tray_id' => $newTray->id,
                ]);

                // Update tray asal: sesi selesai untuk bagian shift ini
                $tray->update([
                    'session_status' => 'HANDED_OVER',
                ]);

                return response()->json([
                    'ok' => true,
                    'message' => "Sisa set yang belum selesai dirakit berhasil diteruskan ke operator {$newOperator} dengan nomor baki {$newTrayCode}!",
                    'new_tray_code' => $newTrayCode,
                    'new_tray_id' => $newTray->id,
                    'moved_parts_count' => count($unfinishedSns),
                    'finished_sets_kept' => $finishedSets->count(),
                ]);
            });
        }

        return response()->json(['ok' => true, 'message' => 'Tindakan sesi berhasil diproses.']);
    }

    /**
     * Pindahkan Set Perakitan ke Gudang
     * Endpoint: POST /api/assembly/transfer-to-warehouse
     */
    public function transferToWarehouse(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        $setNum = $request->input('set_number');
        $reason = $request->input('reason', 'Dipindahkan ke gudang oleh operator');

        if ($assemblyId) {
            Assembly::where('id', $assemblyId)->update([
                'verified_status' => 'RETURNED_TO_WAREHOUSE',
                'notes' => $reason,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => "Set #{$setNum} berhasil dipindahkan ke Gudang!"
        ]);
    }

    /**
     * Pengajuan Daftar Ulang / Perakitan Ulang 1-Set (Operator)
     * Endpoint: POST /api/assembly/reregister-set
     */
    public function reregisterSet(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        $newOperator = $request->input('new_operator', '');
        $reason = $request->input('reason', '');

        $assembly = Assembly::findOrFail($assemblyId);
        $assembly->update([
            'verified_status' => 'PENDING_ADMIN_ASSY_REREG',
            'rereg_pending_operator' => $newOperator ?: $assembly->pic_assembly,
            'reason' => $reason ?: $assembly->reason,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Pengajuan Perakitan Ulang berhasil dikirim ke Admin untuk diverifikasi.'
        ]);
    }

    /**
     * Retest Single Checklist Point TF 1
     * Endpoint: POST /api/assembly/submit-single-retest
     */
    public function submitSingleRetest(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        $testNum = (int)$request->input('test_num');
        $status = $request->input('status', 'Good');
        $notes = $request->input('notes', '');

        $assembly = Assembly::findOrFail($assemblyId);
        $checklist = json_decode($assembly->assy_test_checklist ?: '[]', true);
        if (!is_array($checklist)) {
            $checklist = [];
        }

        $found = false;
        foreach ($checklist as &$item) {
            if (isset($item['test_num']) && (int)$item['test_num'] === $testNum) {
                $item['status'] = $status;
                $item['notes'] = $notes;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $checklist[] = [
                'test_num' => $testNum,
                'status' => $status,
                'notes' => $notes,
            ];
        }

        // Recheck overall status: If any item is Not Good/Hold, overall is Hold TF 1
        $allGood = true;
        foreach ($checklist as $item) {
            $st = strtoupper($item['status'] ?? '');
            if ($st !== 'GOOD' && $st !== 'OK' && $st !== 'PASS') {
                $allGood = false;
                break;
            }
        }
        $overallStatus = $allGood ? 'Good' : 'Hold TF 1';

        $docNumber = $assembly->assy_doc_number;
        if ($allGood && empty($docNumber)) {
            $tray = Tray::find($assembly->tray_id);
            $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
            $docNumber = "WH/WO/{$docSeq}";
        }

        $assembly->update([
            'assy_test_checklist' => json_encode($checklist),
            'assy_test_status' => $overallStatus,
            'assy_doc_number' => $docNumber,
        ]);

        return response()->json([
            'ok' => true,
            'overall_status' => $overallStatus,
            'message' => "Test #{$testNum} berhasil diuji ulang ({$overallStatus})."
        ]);
    }

    /**
     * Opsi "Langsung Lanjut" untuk Set Status Hold / Scrap / Rework
     * Jika saat pengecekan fisik in-aktual ternyata barang tidak bermasalah
     * Endpoint: POST /api/assembly/direct-resume
     */
    public function directResume(Request $request)
    {
        $request->validate([
            'assembly_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $assemblyId = (int)$request->input('assembly_id');
        $notes = $request->input('notes') ?: 'Verifikasi in-aktual barang OK, langsung dilanjutkan Good';
        $actor = auth()->user()?->username ?: 'operator';

        return DB::transaction(function () use ($assemblyId, $notes, $actor) {
            $assembly = Assembly::with('tray')->findOrFail($assemblyId);

            // 1. Jika di tahap Perakitan berstatus Hold/Scrap/Rework
            if (in_array($assembly->status, ['Hold', 'Scrap', 'Rework', 'Decline'])) {
                $oldStatus = $assembly->status;
                $assembly->update([
                    'status' => 'Good',
                    'reason' => null,
                    'defective_parts' => null,
                    'rework_reason' => null,
                    'is_declined' => false,
                    'declined_reason' => null,
                    'finished_at' => $assembly->finished_at ?: now(),
                ]);

                foreach (array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]) as $sn) {
                    MasterStock::where('sn', $sn)->update([
                        'status' => 'Done',
                        'last_reason' => $notes,
                    ]);
                }

                \App\Models\PartAuditLog::create([
                    'part_type' => 'SET',
                    'sn' => $assembly->part15_sn ?: "Set #{$assembly->set_number}",
                    'old_status' => $oldStatus,
                    'new_status' => 'Good',
                    'action_type' => 'ASSEMBLY_DIRECT_RESUME',
                    'actor_username' => $actor,
                    'actor_role' => auth()->user()?->role ?: 'operator',
                    'reason' => $notes,
                ]);

                return $this->successResponse($assembly, "Set #{$assembly->set_number} diverifikasi OK in-aktual dan langsung dilanjutkan dengan status Good.");
            }

            // 2. Jika di tahap TF 1 berstatus Hold / Hold TF 1
            if (in_array($assembly->assy_test_status, ['Hold', 'Hold TF 1', 'HOLD', 'FAIL'])) {
                $oldStatus = $assembly->assy_test_status;
                $tray = Tray::find($assembly->tray_id);
                $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));
                if ($isPU) {
                    preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);
                    $puNum = !empty($m[1]) ? (int)$m[1] : 1;
                    $fallbackTF1 = "ASSY/QC/PU/{$puNum}";
                } else {
                    $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                    $fallbackTF1 = "ASSY/QC/{$docSeq}";
                }
                $docNumber = $assembly->assy_doc_number ?: $fallbackTF1;

                $assembly->update([
                    'assy_test_status' => 'Good',
                    'assy_doc_number' => $docNumber,
                    'assy_failure_reasons' => null,
                ]);

                DB::table('assembly_tests')
                    ->where('assembly_id', $assembly->id)
                    ->where('stage', 'ASSY')
                    ->update([
                        'status' => 'Good',
                        'notes' => $notes,
                    ]);

                \App\Models\PartAuditLog::create([
                    'part_type' => '15',
                    'sn' => $assembly->part15_sn,
                    'old_status' => $oldStatus,
                    'new_status' => 'Good',
                    'action_type' => 'TF1_DIRECT_RESUME',
                    'actor_username' => $actor,
                    'actor_role' => auth()->user()?->role ?: 'operator',
                    'reason' => $notes,
                ]);

                return $this->successResponse($assembly, "Set #{$assembly->set_number} diverifikasi OK in-aktual dan langsung dilanjutkan lolos TF 1 (siap masuk TF 2).");
            }

            // 3. Jika di tahap TF 2 / QC berstatus Hold QC / HOLD_QC
            if (in_array(strtoupper($assembly->qc_status ?? ''), ['HOLD_QC', 'HOLD', 'HOLD TF 2', 'REWORK_QC'])) {
                $oldStatus = $assembly->qc_status;
                $tray = Tray::find($assembly->tray_id);
                $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));
                if ($isPU) {
                    preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);
                    $puNum = !empty($m[1]) ? (int)$m[1] : 1;
                    $fallbackQC = "WH/QC/PU/{$puNum}";
                } else {
                    $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                    $fallbackQC = "WH/QC/{$docSeq}";
                }
                $qcDoc = $assembly->qc_doc_number ?: $fallbackQC;

                $assembly->update([
                    'qc_status' => 'GOOD',
                    'qc_failure_reasons' => null,
                    'qc_doc_number' => $qcDoc,
                    'qc_tested_at' => now(),
                ]);

                DB::table('assembly_tests')
                    ->where('assembly_id', $assembly->id)
                    ->where('stage', 'QC')
                    ->update([
                        'status' => 'Good',
                        'notes' => $notes,
                    ]);

                foreach (array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]) as $sn) {
                    MasterStock::where('sn', $sn)->update([
                        'status' => 'Done',
                        'last_reason' => $notes,
                    ]);
                }

                \App\Models\PartAuditLog::create([
                    'part_type' => '15',
                    'sn' => $assembly->part15_sn,
                    'old_status' => $oldStatus,
                    'new_status' => 'GOOD',
                    'action_type' => 'QC_DIRECT_RESUME',
                    'actor_username' => $actor,
                    'actor_role' => auth()->user()?->role ?: 'qc',
                    'reason' => $notes,
                ]);

                return $this->successResponse($assembly, "Set #{$assembly->set_number} diverifikasi OK in-aktual dan langsung dilanjutkan lolos QC (siap masuk Penerimaan Fisik Gudang).");
            }

            return $this->successResponse($assembly, "Set #{$assembly->set_number} sudah berstatus Good.");
        });
    }
}


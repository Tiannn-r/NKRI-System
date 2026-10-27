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

        $existing = Assembly::where('tray_id', $trayId)
            ->where('set_number', $setNumber)
            ->first();

        if ($existing && !empty($existing->finished_at) && !empty($existing->status)) {
            return response()->json([
                'ok' => false,
                'error' => "Set #{$setNumber} sudah selesai dirakit ({$existing->status}) dan terkunci permanen! Proses yang sudah selesai tidak dapat diulang."
            ], 400);
        }

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
     * Bersihkan status sedang dirakit (in-progress) untuk tray tertentu
     */
    public function clearInProgress(Request $request)
    {
        $trayId = $request->input('tray_id');
        if ($trayId) {
            DB::table('assembly_in_progress')->where('tray_id', $trayId)->delete();
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Menyimpan Hasil Perakitan Set
     */
    public function saveSet(Request $request)
    {
        $p15 = trim((string)($request->input('part15_sn') ?: $request->input('part15', '')));
        $p1 = trim((string)($request->input('part1_sn') ?: $request->input('part1', '')));
        $p12 = trim((string)($request->input('part12_sn') ?: $request->input('part12', '')));

        $request->merge([
            'part15_sn' => $p15,
            'part1_sn' => $p1,
            'part12_sn' => $p12,
        ]);

        $request->validate([
            'tray_id' => 'required|integer',
            'set_number' => 'required|integer',
            'part15_sn' => 'required|string',
            'part12_sn' => 'required|string',
            'part1_sn' => 'required|string',
            'status' => 'required|string', // Good, Hold, Scrap, Rework
        ]);

        return DB::transaction(function () use ($request, $p15, $p1, $p12) {
            $tray = Tray::findOrFail($request->tray_id);
            if ($tray->status !== 'active') {
                return response()->json(['ok' => false, 'error' => "Tray {$tray->tray_code} sudah tidak aktif (status: {$tray->status}) dan tidak dapat dimodifikasi."], 400);
            }

            // Kunci mutlak: Jika set sudah selesai dirakit, dilarang diulang kembali
            $existing = Assembly::where('tray_id', $request->tray_id)
                ->where('set_number', $request->set_number)
                ->first();

            if ($existing && !empty($existing->finished_at) && !empty($existing->status)) {
                return response()->json([
                    'ok' => false,
                    'error' => "Set #{$request->set_number} sudah selesai dirakit dengan status '{$existing->status}' dan terkunci permanen! Proses yang sudah selesai tidak dapat diulang kembali."
                ], 400);
            }

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

            // 2. Validasi kepemilikan part: Part wajib terdaftar di baki ini (tabel parts atau master_stocks)
            foreach ([['15', $p15], ['1', $p1], ['12', $p12]] as [$pType, $sn]) {
                $existsInTray = DB::table('parts')
                    ->where('tray_id', $request->tray_id)
                    ->where('part_type', $pType)
                    ->where('sn', $sn)
                    ->exists();
                if (!$existsInTray) {
                    $existsInMaster = DB::table('master_stocks')
                        ->where('allocated_tray_id', $request->tray_id)
                        ->where('part_type', $pType)
                        ->where('sn', $sn)
                        ->exists();
                    if (!$existsInMaster) {
                        return response()->json([
                            'ok' => false, 
                            'error' => "Part {$pType} SN {$sn} tidak terdaftar pada alokasi baki {$tray->tray_code}!"
                        ], 400);
                    }
                }
            }

            $operator = $request->input('operator') ?: (auth()->user()?->username ?: $request->input('pic_assembly', 'assembly'));

            // Format defective_parts as CSV string (e.g. "15,1" or null)
            $rawDefective = $request->input('defective_parts');
            $defCsv = null;
            $defectiveSet = [];
            if (is_array($rawDefective)) {
                $filtered = array_filter(array_map('trim', $rawDefective));
                if (!empty($filtered)) {
                    $defCsv = implode(',', $filtered);
                    $defectiveSet = $filtered;
                }
            } elseif (is_string($rawDefective) && trim($rawDefective) !== '') {
                $defCsv = trim($rawDefective);
                $defectiveSet = array_filter(array_map('trim', explode(',', $defCsv)));
            }

            $reason = ($request->status !== 'Good') ? trim((string)$request->input('reason', '')) : null;

            $assembly = Assembly::updateOrCreate(
                [
                    'tray_id' => $request->tray_id,
                    'set_number' => $request->set_number,
                ],
                [
                    'pic_assembly' => $operator,
                    'part15_sn' => $p15,
                    'part12_sn' => $p12,
                    'part1_sn' => $p1,
                    'status' => $request->status,
                    'original_assembly_status' => ($existing && !empty($existing->original_assembly_status)) ? $existing->original_assembly_status : $request->status,
                    'reason' => $reason,
                    'defective_parts' => $defCsv,
                    'started_at' => $request->input('started_at') ?: now(),
                    'finished_at' => now(),
                ]
            );

            // Update status di tabel parts dan master_stocks (sesuai original app.py)
            $partMapping = [['15', $p15], ['12', $p12], ['1', $p1]];
            foreach ($partMapping as [$pType, $sn]) {
                if ($request->status === 'Good') {
                    DB::table('parts')->where('sn', $sn)->where('tray_id', $tray->id)->update(['status' => 'Done']);
                    MasterStock::where('sn', $sn)->update([
                        'status' => 'Done',
                        'used_at' => now(),
                    ]);
                } else {
                    $isDefective = in_array($pType, $defectiveSet) || in_array($sn, $defectiveSet) || in_array("part{$pType}", $defectiveSet) || in_array("p{$pType}", $defectiveSet);
                    $targetStatus = $isDefective ? $request->status : 'Good';
                    $partStatus = $isDefective ? $request->status : 'Good';
                    DB::table('parts')->where('sn', $sn)->where('tray_id', $tray->id)->update(['status' => $partStatus]);
                    MasterStock::where('sn', $sn)->update([
                        'status' => $targetStatus,
                        'used_at' => now(),
                    ]);
                }
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
                'reason' => $reason,
                'defective_parts' => $defCsv,
                'started_at' => $assembly->started_at,
                'finished_at' => $assembly->finished_at,
                'source' => 'ASSEMBLY',
            ]);

            // Bersihkan slot in-progress jika ada
            DB::table('assembly_in_progress')->where('tray_id', $tray->id)->delete();

            // Update assigned operator jika masih kosong / generic
            if (empty($tray->assigned_operator) || in_array($tray->assigned_operator, ['ALL', 'Semua Operator', 'Semua Operator Assembly'])) {
                $tray->update(['assigned_operator' => $operator]);
            }

            // Cek apakah seluruh 22 set telah selesai dirakit
            $totalFinished = Assembly::where('tray_id', $tray->id)
                ->whereNotNull('finished_at')
                ->whereNotNull('status')
                ->count();
            if ($totalFinished >= 22) {
                $tray->update(['session_status' => 'COMPLETED']);
            }

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
        $downtimeId = $request->input('downtime_id');
        $trayId = $request->input('tray_id');
        $trayCode = $request->input('tray_code');
        $operator = auth()->user()?->username ?: $request->input('operator');

        $downtime = null;
        if ($downtimeId) {
            $downtime = AssemblyDowntime::where('id', $downtimeId)->whereNull('resumed_at')->first();
        }
        if (!$downtime && $trayId) {
            $downtime = AssemblyDowntime::where('tray_id', $trayId)->whereNull('resumed_at')->latest('id')->first();
        }
        if (!$downtime && $trayCode) {
            $downtime = AssemblyDowntime::where('tray_code', $trayCode)->whereNull('resumed_at')->latest('id')->first();
        }

        if ($downtime) {
            $resumedAt = now();
            $duration = $downtime->paused_at ? $downtime->paused_at->diffInSeconds($resumedAt) : 0;
            $downtime->update([
                'resumed_at' => $resumedAt,
                'duration_seconds' => $duration,
            ]);
        }

        // Tutup seluruh downtime yang masih menggantung pada tray ini agar tidak memicu jeda berulang
        if ($trayId || $trayCode) {
            AssemblyDowntime::where(function ($w) use ($trayId, $trayCode) {
                if ($trayId) $w->where('tray_id', $trayId);
                if ($trayCode) $w->orWhere('tray_code', $trayCode);
            })->whereNull('resumed_at')->update([
                'resumed_at' => now(),
                'duration_seconds' => 0
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
     * Ganti Part Perakitan (Replace Part Request)
     * Mengajukan pergantian 1 atau lebih part bermasalah ke antrean Verifikasi Admin
     * Endpoint: POST /api/assembly/replace-part
     */
    public function replacePart(Request $request)
    {
        $assemblyId = (int)$request->input('assembly_id');
        $reason = trim((string)$request->input('reason', '')) ?: 'Penggantian part bermasalah';
        $oldPartStatus = trim((string)$request->input('old_part_status', 'Hold'));
        if (!in_array($oldPartStatus, ['Hold', 'Rework', 'Scrap'])) {
            $oldPartStatus = 'Hold';
        }

        // Mendukung array replacements (multi-part) maupun legacy single part
        $replacements = $request->input('replacements');
        if (!is_array($replacements) || empty($replacements)) {
            $pType = trim((string)$request->input('part_type', ''));
            $nSn = trim((string)$request->input('new_sn', ''));
            if ($pType && $nSn) {
                $replacements = [['part_type' => $pType, 'new_sn' => $nSn]];
            } else {
                $replacements = [];
            }
        }

        if (!$assemblyId || empty($replacements)) {
            return response()->json([
                'ok' => false,
                'error' => 'Data rakitan (assembly_id) dan minimal 1 part baru pengganti wajib diisi.'
            ], 422);
        }

        $operator = auth()->user()?->username ?: $request->input('operator', 'operator');
        $operatorRole = auth()->user()?->role ?: 'operator';

        return DB::transaction(function () use ($assemblyId, $replacements, $reason, $oldPartStatus, $operator, $operatorRole) {
            $assembly = Assembly::with('tray')->find($assemblyId);
            if (!$assembly) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Data perakitan tidak ditemukan.'
                ], 404);
            }

            $trayId = $assembly->tray_id;
            $trayCode = $assembly->tray?->tray_code ?: '-';

            // Cek apakah sudah ada permohonan ganti part yang masih PENDING untuk rakitan ini
            $existingPending = DB::table('part_replacement_requests')
                ->where('assembly_id', $assemblyId)
                ->where('status', 'PENDING')
                ->first();

            if ($existingPending || $assembly->verified_status === 'PENDING_REPLACEMENT') {
                return response()->json([
                    'ok' => false,
                    'error' => 'Rakitan ini sudah memiliki pengajuan pergantian part yang sedang menunggu verifikasi Administrator.'
                ], 400);
            }

            $detailedReplacements = [];

            foreach ($replacements as $rep) {
                if (!is_array($rep)) continue;
                $pType = trim((string)($rep['part_type'] ?? ''));
                $nSn = trim((string)($rep['new_sn'] ?? ''));

                if (!$pType || !$nSn || !in_array($pType, ['15', '1', '12'])) {
                    continue;
                }

                $newStock = MasterStock::where('part_type', $pType)
                    ->where(function ($w) use ($nSn) {
                        $w->where('sn', $nSn)
                          ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $nSn));
                    })
                    ->whereIn('status', ['Tersedia', 'Good'])
                    ->first();

                if (!$newStock) {
                    return response()->json([
                        'ok' => false,
                        'error' => "Part baru {$pType} SN {$nSn} tidak ditemukan atau statusnya bukan 'Tersedia' di Master Stok!"
                    ], 400);
                }

                // Pastikan part baru tidak sedang aktif digunakan di set lain pada tray yang sama
                $activeInSame = Assembly::where('tray_id', $trayId)
                    ->where('id', '!=', $assemblyId)
                    ->where(function ($w) use ($nSn, $pType) {
                        if ($pType === '15') $w->where('part15_sn', $nSn);
                        elseif ($pType === '1') $w->where('part1_sn', $nSn);
                        elseif ($pType === '12') $w->where('part12_sn', $nSn);
                    })
                    ->where(function ($w) {
                        $w->whereNull('is_reregistered')->orWhere('is_reregistered', 0);
                    })
                    ->first();

                if ($activeInSame) {
                    return response()->json([
                        'ok' => false,
                        'error' => "Part {$pType} SN {$nSn} sedang aktif digunakan di Set #{$activeInSame->set_number} pada tray ini!"
                    ], 400);
                }

                $oldSn = match ($pType) {
                    '15' => $assembly->part15_sn,
                    '1' => $assembly->part1_sn,
                    '12' => $assembly->part12_sn,
                    default => '-',
                };

                $detailedReplacements[] = [
                    'part_type' => $pType,
                    'old_sn' => $oldSn,
                    'new_sn' => $newStock->sn,
                    'new_stock_id' => $newStock->id,
                ];
            }

            if (empty($detailedReplacements)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Tidak ada part valid yang diajukan untuk diganti.'
                ], 400);
            }

            // Tandai part baru di master_stocks sebagai 'Pending Verif Admin' agar ter-reserve
            foreach ($detailedReplacements as $dr) {
                MasterStock::where('id', $dr['new_stock_id'])->update([
                    'status' => 'Pending Verif Admin',
                    'last_reason' => "Diajukan ganti part Set #{$assembly->set_number} WO {$trayCode}: {$reason}",
                    'allocated_tray_id' => $trayId,
                    'last_operator' => auth()->user()?->full_name ?: $operator,
                ]);

                DB::table('part_audit_logs')->insert([
                    'part_type' => $dr['part_type'],
                    'sn' => $dr['new_sn'],
                    'old_status' => 'Tersedia',
                    'new_status' => 'Pending Verif Admin',
                    'action_type' => 'REPLACE_PART_REQUESTED',
                    'actor_username' => $operator,
                    'actor_role' => $operatorRole,
                    'reason' => "Diajukan ganti part Set #{$assembly->set_number} WO {$trayCode} menggantikan SN {$dr['old_sn']}: {$reason}",
                    'created_at' => now(),
                ]);
            }

            // Simpan permohonan ke part_replacement_requests untuk diverifikasi Admin
            $replacementsJson = array_map(function ($r) {
                return [
                    'part_type' => $r['part_type'],
                    'old_sn' => $r['old_sn'],
                    'new_sn' => $r['new_sn'],
                ];
            }, $detailedReplacements);

            DB::table('part_replacement_requests')->insert([
                'assembly_id' => $assemblyId,
                'tray_id' => $trayId,
                'set_number' => $assembly->set_number,
                'replacements_json' => json_encode($replacementsJson),
                'old_part_status' => $oldPartStatus,
                'reason' => $reason,
                'requestor_username' => $operator,
                'requestor_role' => $operatorRole,
                'status' => 'PENDING',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update status verifikasi assembly menjadi PENDING_REPLACEMENT
            $assembly->update([
                'original_assembly_status' => $assembly->original_assembly_status ?: $assembly->status,
                'verified_status' => 'PENDING_REPLACEMENT',
            ]);

            // Hapus re_register_requests pending untuk part rakitan ini jika ada
            DB::table('re_register_requests')
                ->whereIn('sn', array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]))
                ->where('status', 'PENDING')
                ->delete();

            $count = count($detailedReplacements);
            return response()->json([
                'ok' => true,
                'message' => "Permohonan penggantian {$count} part pada Set #{$assembly->set_number} berhasil diajukan untuk verifikasi Administrator!"
            ]);
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
            ->leftJoin('users as u_wh', function ($join) {
                $join->on('t.pic_warehouse', '=', 'u_wh.username')
                     ->orOn('t.pic_warehouse', '=', 'u_wh.full_name');
            })
            ->select([
                't.id as tray_id',
                't.tray_code',
                't.source_document_mo',
                DB::raw("COALESCE(t.product_type, 'Nanggala P9') as product_type"),
                't.status as tray_status',
                't.created_at as tray_created_at',
                DB::raw("COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse"),
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly, t.assigned_operator) as pic_assembly"),
                'a.id as assembly_id',
                'a.set_number',
                'a.part15_sn',
                'a.part12_sn',
                'a.part1_sn',
                DB::raw("COALESCE(a.status, 'Good') as status"),
                DB::raw("COALESCE(a.status, 'Good') as assembly_status"),
                'a.original_assembly_status',
                'a.reason',
                DB::raw("COALESCE(a.reason, '') as assembly_reason"),
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

        $results = $query->orderBy('t.tray_code', 'asc')->orderBy('a.set_number', 'asc')->get();

        $traysDict = [];
        $uniqueRows = [];
        $seenAssemblyIds = [];

        foreach ($results as $row) {
            $aid = $row->assembly_id;
            if ($aid && isset($seenAssemblyIds[$aid])) {
                continue;
            }
            if ($aid) {
                $seenAssemblyIds[$aid] = true;
            }
            $r = (array)$row;
            $r['tray_code'] = $row->tray_code;
            $r['source_document_mo'] = $row->source_document_mo;
            $r['pic_assembly'] = $row->pic_assembly;
            $r['pic_warehouse'] = $row->pic_warehouse;
            $r['product_type'] = $row->product_type;
            $uniqueRows[] = $r;

            $tid = $row->tray_id;
            if (!isset($traysDict[$tid])) {
                $traysDict[$tid] = [
                    'tray_id' => $tid,
                    'tray_code' => $row->tray_code,
                    'source_document_mo' => $row->source_document_mo,
                    'product_type' => $row->product_type,
                    'tray_status' => $row->tray_status ?? 'active',
                    'tray_created_at' => $row->tray_created_at,
                    'pic_assembly' => $row->pic_assembly,
                    'pic_warehouse' => $row->pic_warehouse,
                    'sets_count' => 0,
                    'good_count' => 0,
                    'hold_count' => 0,
                    'rework_count' => 0,
                    'scrap_count' => 0,
                    'declined_count' => 0,
                    'items' => [],
                ];
            }
            $traysDict[$tid]['items'][] = $r;
            $traysDict[$tid]['sets_count']++;
            $st = strtoupper(trim((string)($row->status ?? 'Good')));
            if ($st === 'HOLD') {
                $traysDict[$tid]['hold_count']++;
            } elseif ($st === 'REWORK') {
                $traysDict[$tid]['rework_count']++;
            } elseif ($st === 'SCRAP') {
                $traysDict[$tid]['scrap_count']++;
            } elseif ($st === 'DECLINED' || !empty($row->is_declined)) {
                $traysDict[$tid]['declined_count']++;
            } else {
                $traysDict[$tid]['good_count']++;
            }
        }

        $grouped = array_values($traysDict);
        return response()->json([
            'ok' => true,
            'data' => $grouped,
            'total_trays' => count($grouped),
            'total_items' => count($uniqueRows),
        ]);
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


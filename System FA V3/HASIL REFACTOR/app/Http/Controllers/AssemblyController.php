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
            $assembly = Assembly::updateOrCreate(
                [
                    'tray_id' => $request->tray_id,
                    'set_number' => $request->set_number,
                ],
                [
                    'pic_assembly' => auth()->user()?->username ?: $request->input('pic_assembly', 'assembly'),
                    'part15_sn' => trim($request->part15_sn),
                    'part12_sn' => trim($request->part12_sn),
                    'part1_sn' => trim($request->part1_sn),
                    'status' => $request->status,
                    'reason' => $request->input('reason'),
                    'defective_parts' => $request->input('defective_parts'),
                    'started_at' => $request->input('started_at') ?: now(),
                    'finished_at' => now(),
                ]
            );

            // Update status master stocks jika Done/Good
            $stockStatus = ($request->status === 'Good') ? 'Done' : $request->status;
            foreach ([$request->part15_sn, $request->part1_sn, $request->part12_sn] as $sn) {
                MasterStock::where('sn', trim($sn))->update([
                    'status' => $stockStatus,
                    'used_at' => now(),
                ]);
            }

            // Catat ke WO History
            $tray = Tray::find($request->tray_id);
            AssemblyWoHistory::create([
                'tray_id' => $assembly->tray_id,
                'tray_code' => $tray?->tray_code,
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

        $checklist = $request->test_checklist;
        $hasFailed = collect($checklist)->contains(fn ($t) => ($t['status'] ?? '') === 'NOT_GOOD');

        $assembly->update([
            'test_checklist' => $checklist,
            'notes' => $request->input('notes'),
            'assy_tester_username' => auth()->user()?->username ?: 'tester',
            'assy_tested_at' => now(),
            'assy_doc_number' => $request->input('assy_doc_number'),
            // Jika ada yang failed di TF 1, otomatis beri catatan kendala
            'reason' => $hasFailed ? ($request->input('notes') ?: 'Kendala pada uji fungsional 1') : $assembly->reason,
        ]);

        return $this->successResponse($assembly, "Hasil Uji Fungsional 1 (TF 1) Set #{$assembly->set_number} tersimpan.");
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

            // Update master stock part lama
            if ($oldSn && $oldSn !== '-') {
                MasterStock::where('sn', $oldSn)->where('part_type', $partType)->update([
                    'status' => 'Part Diganti',
                    'is_replaced_from_assy' => 1,
                    'last_reason' => $reason,
                    'last_operator' => $operator,
                ]);

                // Audit log
                DB::table('part_audit_logs')->insert([
                    'part_type' => $partType,
                    'sn' => $oldSn,
                    'old_status' => 'Supplied',
                    'new_status' => 'Part Diganti',
                    'action_type' => 'REPLACE_PART',
                    'actor_username' => $operator,
                    'actor_role' => auth()->user()?->role ?: 'operator',
                    'reason' => "Diganti dengan SN {$newSn}: {$reason}",
                    'created_at' => now(),
                ]);
            }

            return $this->successResponse($assy, "Part {$partType} SN {$oldSn} berhasil diganti dengan SN {$newSn}.");
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

}

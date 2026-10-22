<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterStock;
use App\Models\PartAuditLog;

class MasterStockController extends Controller
{
    /**
     * Tampilan Stok Master: Tanpa Batasan (Uncapped)
     * Memenuhi Poin 2: Wajib tersedia dan tidak ada batasan berapapun stocknya harus ditampilkan
     */
    public function stocksList(Request $request)
    {
        $type = $request->input('type');
        $status = $request->input('status');
        $replaced = $request->input('replaced');
        $q = trim($request->input('q', ''));
        $page = max(1, (int)$request->input('page', 1));
        
        // Cek apakah mode uncapped / all diminta
        $limitParam = $request->input('limit');
        $uncapped = ($limitParam === 'all' || $limitParam === '0' || $limitParam === '-1');
        $limit = $uncapped ? 25000 : max(1, (int)($limitParam ?: 100));
        $offset = ($page - 1) * $limit;

        $query = DB::table('master_stocks as m')
            ->leftJoin('trays as t', function ($join) {
                $join->on(DB::raw('COALESCE(m.allocated_tray_id, m.last_tray_id)'), '=', 't.id');
            })
            ->leftJoin('users as u_wh', function ($join) {
                $join->on('t.pic_warehouse', '=', 'u_wh.username')
                    ->orOn('t.pic_warehouse', '=', 'u_wh.full_name');
            })
            ->leftJoin('users as u_op', function ($join) {
                $join->on('t.assigned_operator', '=', 'u_op.username')
                    ->orOn('t.assigned_operator', '=', 'u_op.full_name');
            })
            ->leftJoin('assemblies as a', function ($join) {
                $join->on('m.allocated_tray_id', '=', 'a.tray_id')
                    ->where(function ($w) {
                        $w->whereColumn('m.sn', 'a.part15_sn')
                          ->orWhereColumn('m.sn', 'a.part1_sn')
                          ->orWhereColumn('m.sn', 'a.part12_sn');
                    });
            })
            ->leftJoin('users as u_assy', function ($join) {
                $join->on('a.pic_assembly', '=', 'u_assy.username')
                    ->orOn('a.pic_assembly', '=', 'u_assy.full_name');
            });

        if ($type) {
            $query->where('m.part_type', $type);
        }

        if ($replaced !== null && $replaced !== '') {
            $isReplaced = in_array(strtolower($replaced), ['1', 'true', 'yes', 'replaced']);
            $query->where(DB::raw('COALESCE(m.is_replaced_from_assy, 0)'), $isReplaced ? 1 : 0);
        }

        if ($status) {
            if (in_array(strtolower($status), ['part diganti', 'replaced'])) {
                $query->where(DB::raw('COALESCE(m.is_replaced_from_assy, 0)'), 1);
            } elseif (in_array(strtolower($status), ['decline', 'declined', 'pending verif admin'])) {
                $query->where(function ($w) {
                    $w->where('a.is_declined', 1)
                      ->orWhere('a.verified_status', 'DECLINED')
                      ->orWhereIn('m.status', ['Declined', 'Decline', 'Pending Verif Admin']);
                });
            } else {
                $query->where(function ($w) use ($status) {
                    $w->where(function ($inner) {
                        $inner->where('a.is_declined', '!=', 1)
                              ->where('a.verified_status', '!=', 'DECLINED')
                              ->whereNotIn('m.status', ['Declined', 'Decline', 'Pending Verif Admin']);
                    })->where(DB::raw('COALESCE(a.status, m.status, "Tersedia")'), $status);
                });
            }
        }

        if ($q) {
            $qClean = str_replace('.', '', $q);
            $query->where(function ($w) use ($q, $qClean) {
                $w->where('m.sn', 'like', "%{$q}%")
                  ->orWhere(DB::raw("REPLACE(m.sn, '.', '')"), 'like', "%{$qClean}%")
                  ->orWhere('t.tray_code', 'like', "%{$q}%")
                  ->orWhere('t.source_document_mo', 'like', "%{$q}%")
                  ->orWhere('u_op.full_name', 'like', "%{$q}%")
                  ->orWhere('u_wh.full_name', 'like', "%{$q}%");
            });
        }

        $total = $query->count('m.id');

        $rows = $query->select([
            'm.id',
            'm.part_type',
            'm.sn',
            'm.cs',
            'm.status as stock_status',
            'm.allocated_tray_id',
            'm.allocated_at',
            'm.used_at',
            'm.last_reason',
            DB::raw('COALESCE(m.is_replaced_from_assy, 0) as is_replaced_from_assy'),
            't.tray_code',
            DB::raw('COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse'),
            DB::raw('COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator'),
            't.source_document_mo',
            'a.set_number',
            DB::raw('COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly'),
            'a.started_at',
            'a.finished_at',
            'a.status as assembly_status',
            'a.reason as assembly_reason',
            'a.is_declined',
            'a.verified_status',
            'a.declined_reason',
            DB::raw('CASE 
                WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN "Declined"
                WHEN COALESCE(m.is_replaced_from_assy, 0) = 1 THEN "Part Diganti"
                ELSE COALESCE(a.status, m.status, "Tersedia")
            END as display_status')
        ])
        ->orderBy('m.sn', 'asc')
        ->offset($offset)
        ->limit($limit)
        ->get();

        return response()->json([
            'ok' => true,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => max(1, (int)ceil($total / $limit)),
            'data' => $rows,
        ]);
    }

    /**
     * Ringkasan Stok Master per Part Type (1, 12, 15)
     */
    public function stocksSummary()
    {
        $rows = DB::table('master_stocks as m')
            ->leftJoin('assemblies as a', function ($join) {
                $join->on('m.allocated_tray_id', '=', 'a.tray_id')
                    ->where(function ($w) {
                        $w->whereColumn('m.sn', 'a.part15_sn')
                          ->orWhereColumn('m.sn', 'a.part1_sn')
                          ->orWhereColumn('m.sn', 'a.part12_sn');
                    });
            })
            ->select([
                'm.part_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status, "Tersedia") = "Tersedia" THEN 1 END) as available'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status) = "Supplied" THEN 1 END) as allocated'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status) = "Done" THEN 1 END) as used'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status) IN ("Good", "Done") THEN 1 END) as good_count'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status) = "Hold" THEN 1 END) as hold_count'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status) = "Scrap" THEN 1 END) as scrap_count'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN COALESCE(a.status, m.status) = "Rework" THEN 1 END) as rework_count'),
                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN 1 END) as decline_count'),
                DB::raw('COUNT(CASE WHEN COALESCE(m.is_replaced_from_assy, 0) = 1 THEN 1 END) as replaced_count'),
            ])
            ->groupBy('m.part_type')
            ->orderBy('m.part_type')
            ->get();

        return $this->successResponse($rows);
    }

    /**
     * Mengambil nomor seri yang Tersedia untuk alokasi Tray baru Gudang
     */
    public function available(Request $request)
    {
        $partType = $request->input('type');
        $q = trim($request->input('q', ''));
        $limitParam = $request->input('limit');

        $query = MasterStock::where('status', 'Tersedia')
            ->whereNull('allocated_tray_id');

        if ($partType) {
            $query->where('part_type', $partType);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('sn', 'like', "%{$q}%")
                  ->orWhere(DB::raw("REPLACE(sn, '.', '')"), 'like', "%{$q}%");
            });
        }

        $query->orderBy('sn', 'asc');

        if ($limitParam && is_numeric($limitParam) && (int)$limitParam > 0) {
            $query->limit((int)$limitParam);
        }

        $items = $query->select('sn', 'cs', 'part_type', 'status')->get();

        return $this->successResponse($items);
    }

    /**
     * Detail pelacakan part (Traceability Drilldown)
     */
    public function partDetail(Request $request)
    {
        $sn = trim($request->input('sn', ''));
        if (!$sn) {
            return $this->errorResponse('Nomor seri part diperlukan');
        }

        $stock = MasterStock::where('sn', $sn)
            ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn))
            ->first();

        if (!$stock) {
            return $this->errorResponse('Data part tidak ditemukan', 404);
        }

        $tray = $stock->allocated_tray_id ? DB::table('trays')->where('id', $stock->allocated_tray_id)->first() : null;
        $assembly = DB::table('assemblies')
            ->where(function ($w) use ($sn) {
                $w->where('part15_sn', $sn)
                  ->orWhere('part1_sn', $sn)
                  ->orWhere('part12_sn', $sn);
            })->first();

        $auditLogs = DB::table('part_audit_logs')
            ->where('sn', $sn)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse([
            'stock' => $stock,
            'tray' => $tray,
            'assembly' => $assembly,
            'audit_logs' => $auditLogs,
        ]);
    }

    /**
     * Tambah Single Part Number Baru ke Master Stok
     */
    public function addPart(Request $request)
    {
        $partType = trim((string)$request->input('part_type', ''));
        $rawSn = trim((string)$request->input('sn', ''));
        $reason = trim((string)$request->input('reason', '')) ?: 'Tambah manual part number';

        if (!in_array($partType, ['1', '12', '15'])) {
            return $this->errorResponse("Part Type harus '1', '12', atau '15'", 400);
        }

        if (!$rawSn) {
            return $this->errorResponse("Serial Number wajib diisi!", 400);
        }

        if (in_array($partType, ['1', '12'])) {
            $cleanSn = str_replace(['.', ',', ' '], '', $rawSn);
            if (!ctype_digit($cleanSn)) {
                return $this->errorResponse("SN Part {$partType} harus berupa angka", 400);
            }
            $csVal = null;
        } else {
            $cleanSn = strtoupper(str_replace(' ', '', $rawSn));
            $csVal = strtoupper(trim((string)$request->input('cs', '')));
            if (!$cleanSn || !ctype_alpha($cleanSn[0])) {
                return $this->errorResponse("SN Part 15 harus diawali huruf (contoh: K1103581)", 400);
            }
            if (!$csVal) {
                return $this->errorResponse("CS wajib diisi untuk Part 15", 400);
            }
        }

        // Cek duplikasi di master_stocks
        $cleanDot = str_replace('.', '', $cleanSn);
        $existing = DB::table('master_stocks')
            ->where('part_type', $partType)
            ->where(function ($w) use ($cleanSn, $cleanDot) {
                $w->where('sn', $cleanSn)
                  ->orWhere(DB::raw("REPLACE(sn, '.', '')"), $cleanDot);
            })
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => false,
                'error' => "Part {$partType} SN {$cleanSn} sudah terdaftar di Master Stok (Status saat ini: {$existing->status})"
            ], 409);
        }

        $user = auth()->user();
        $actorUsername = $user?->username ?: 'admin';
        $actorRole = $user?->role ?: 'admin';

        DB::beginTransaction();
        try {
            DB::table('master_stocks')->insert([
                'sn' => $cleanSn,
                'cs' => $csVal,
                'part_type' => $partType,
                'status' => 'Tersedia',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('part_audit_logs')->insert([
                'part_type' => $partType,
                'sn' => $cleanSn,
                'old_status' => '-',
                'new_status' => 'Tersedia',
                'action_type' => 'ADD_PART_NUMBER',
                'actor_username' => $actorUsername,
                'actor_role' => $actorRole,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => "Part {$partType} SN {$cleanSn} berhasil ditambahkan dengan status 'Tersedia'.",
                'data' => [
                    'sn' => $cleanSn,
                    'cs' => $csVal,
                    'part_type' => $partType,
                    'status' => 'Tersedia',
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse("Gagal menambahkan part: " . $e->getMessage(), 500);
        }
    }

    /**
     * Tambah Bulk Part Number (Mode Rentang / List)
     */
    public function addPartBulk(Request $request)
    {
        $partType = trim((string)$request->input('part_type', ''));
        if (!in_array($partType, ['1', '12', '15'])) {
            return $this->errorResponse("Part Type harus '1', '12', atau '15'", 400);
        }

        $reason = trim((string)$request->input('reason', '')) ?: 'Tambah bulk part number';
        $csVal = ($partType === '15') ? strtoupper(trim((string)$request->input('cs', ''))) : null;
        if ($partType === '15' && !$csVal) {
            return $this->errorResponse("CS wajib diisi untuk Part 15", 400);
        }

        $snStart = trim((string)$request->input('sn_start', ''));
        $snEnd = trim((string)$request->input('sn_end', ''));
        $snsRaw = $request->input('sns');

        $targetSns = [];

        // Mode 1: Rentang (hanya numerik Part 1 & 12)
        if ($snStart && $snEnd) {
            if ($partType === '15') {
                return $this->errorResponse("Mode rentang tidak mendukung Part 15", 400);
            }
            $cStart = str_replace(['.', ',', ' '], '', $snStart);
            $cEnd = str_replace(['.', ',', ' '], '', $snEnd);
            if (!ctype_digit($cStart) || !ctype_digit($cEnd)) {
                return $this->errorResponse("SN awal dan SN akhir harus berupa angka", 400);
            }
            $vStart = (int)$cStart;
            $vEnd = (int)$cEnd;
            if ($vStart > $vEnd) {
                return $this->errorResponse("SN awal tidak boleh lebih besar dari SN akhir", 400);
            }
            if (($vEnd - $vStart + 1) > 500) {
                return $this->errorResponse("Maksimal rentang bulk adalah 500 part sekaligus", 400);
            }
            $padLen = max(strlen($cStart), strlen($cEnd));
            for ($v = $vStart; $v <= $vEnd; $v++) {
                $targetSns[] = str_pad((string)$v, $padLen, '0', STR_PAD_LEFT);
            }
        } elseif ($snsRaw !== null) {
            if (is_string($snsRaw)) {
                $lines = preg_split('/[\r\n,]+/', $snsRaw, -1, PREG_SPLIT_NO_EMPTY);
            } elseif (is_array($snsRaw)) {
                $lines = $snsRaw;
            } else {
                $lines = [];
            }

            foreach ($lines as $rawS) {
                $rawS = trim((string)$rawS);
                if (!$rawS) continue;
                if (in_array($partType, ['1', '12'])) {
                    $cSn = str_replace(['.', ',', ' '], '', $rawS);
                    if (ctype_digit($cSn) && !in_array($cSn, $targetSns)) {
                        $targetSns[] = $cSn;
                    }
                } else {
                    $cSn = strtoupper(str_replace(' ', '', $rawS));
                    if ($cSn && ctype_alpha($cSn[0]) && !in_array($cSn, $targetSns)) {
                        $targetSns[] = $cSn;
                    }
                }
            }
        } else {
            return $this->errorResponse("Harap masukkan daftar SN atau tentukan rentang SN awal-akhir", 400);
        }

        if (empty($targetSns)) {
            return $this->errorResponse("Tidak ada Serial Number yang valid untuk diproses", 400);
        }

        $user = auth()->user();
        $actorUsername = $user?->username ?: 'admin';
        $actorRole = $user?->role ?: 'admin';

        $added = [];
        $duplicates = [];

        DB::beginTransaction();
        try {
            foreach ($targetSns as $itemSn) {
                $cleanDot = str_replace('.', '', $itemSn);
                $existing = DB::table('master_stocks')
                    ->where('part_type', $partType)
                    ->where(function ($w) use ($itemSn, $cleanDot) {
                        $w->where('sn', $itemSn)
                          ->orWhere(DB::raw("REPLACE(sn, '.', '')"), $cleanDot);
                    })
                    ->first();

                if ($existing) {
                    $duplicates[] = ['sn' => $itemSn, 'status' => $existing->status];
                } else {
                    DB::table('master_stocks')->insert([
                        'sn' => $itemSn,
                        'cs' => $csVal,
                        'part_type' => $partType,
                        'status' => 'Tersedia',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('part_audit_logs')->insert([
                        'part_type' => $partType,
                        'sn' => $itemSn,
                        'old_status' => '-',
                        'new_status' => 'Tersedia',
                        'action_type' => 'ADD_PART_NUMBER',
                        'actor_username' => $actorUsername,
                        'actor_role' => $actorRole,
                        'reason' => $reason,
                        'created_at' => now(),
                    ]);
                    $added[] = $itemSn;
                }
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => "Berhasil menambahkan " . count($added) . " part baru. (" . count($duplicates) . " part sudah terdaftar sebelumnya)",
                'data' => [
                    'part_type' => $partType,
                    'added_count' => count($added),
                    'duplicate_count' => count($duplicates),
                    'added' => $added,
                    'duplicates' => $duplicates,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse("Gagal menambahkan bulk part: " . $e->getMessage(), 500);
        }
    }

    /**
     * Hapus Part Number dari Master Stok (Hanya jika masih Tersedia & Belum Dipakai)
     */
    public function deletePart(Request $request)
    {
        $partId = $request->input('id');
        $partType = trim((string)$request->input('part_type', ''));
        $rawSn = trim((string)$request->input('sn', ''));

        if (!$partId && (!$partType || !$rawSn)) {
            return $this->errorResponse("ID part atau Part Type & SN wajib disertakan", 400);
        }

        $query = DB::table('master_stocks');
        if ($partId) {
            $query->where('id', $partId);
        } else {
            $cleanSn = in_array($partType, ['1', '12']) ? str_replace(['.', ',', ' '], '', $rawSn) : strtoupper(str_replace(' ', '', $rawSn));
            $cleanDot = str_replace('.', '', $cleanSn);
            $query->where('part_type', $partType)
                  ->where(function ($w) use ($cleanSn, $cleanDot) {
                      $w->where('sn', $cleanSn)
                        ->orWhere(DB::raw("REPLACE(sn, '.', '')"), $cleanDot);
                  });
        }

        $partRow = $query->first();
        if (!$partRow) {
            return $this->errorResponse("Part tidak ditemukan di Master Stok", 404);
        }

        if ($partRow->status !== 'Tersedia' || $partRow->allocated_tray_id) {
            return response()->json([
                'ok' => false,
                'error' => "Part {$partRow->part_type} SN {$partRow->sn} tidak dapat dihapus karena statusnya sudah '{$partRow->status}' atau sedang digunakan dalam produksi."
            ], 400);
        }

        $user = auth()->user();
        $actorUsername = $user?->username ?: 'admin';
        $actorRole = $user?->role ?: 'admin';

        DB::beginTransaction();
        try {
            DB::table('part_audit_logs')->insert([
                'part_type' => $partRow->part_type,
                'sn' => $partRow->sn,
                'old_status' => $partRow->status,
                'new_status' => 'Deleted',
                'action_type' => 'DELETE_PART_NUMBER',
                'actor_username' => $actorUsername,
                'actor_role' => $actorRole,
                'reason' => 'Dihapus dari menu Tambah Part',
                'created_at' => now(),
            ]);

            DB::table('master_stocks')->where('id', $partRow->id)->delete();

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => "Part {$partRow->part_type} SN {$partRow->sn} berhasil dihapus dari Master Stok.",
                'data' => [
                    'id' => $partRow->id,
                    'sn' => $partRow->sn,
                    'part_type' => $partRow->part_type,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse("Gagal menghapus part: " . $e->getMessage(), 500);
        }
    }

    /**
     * Mengambil daftar part yang baru saja ditambahkan
     */
    public function recentAdded(Request $request)
    {
        $limitParam = $request->input('limit', 20);
        $limit = is_numeric($limitParam) ? min(100, max(1, (int)$limitParam)) : 20;
        $partType = trim((string)$request->input('part_type', '')) ?: trim((string)$request->input('type', ''));

        $query = DB::table('master_stocks as m')
            ->select([
                'm.id',
                'm.sn',
                'm.cs',
                'm.part_type',
                'm.status',
                'm.created_at',
                'pal.actor_username',
                'pal.actor_role',
                'pal.reason'
            ])
            ->leftJoin('part_audit_logs as pal', function ($join) {
                $join->on('pal.sn', '=', 'm.sn')
                     ->where('pal.action_type', '=', 'ADD_PART_NUMBER');
            });

        if ($partType) {
            $query->where('m.part_type', $partType);
        }

        $rows = $query->orderBy('m.id', 'desc')->limit($limit)->get();

        return response()->json([
            'ok' => true,
            'data' => $rows
        ]);
    }


    // Aliases to match routes
    public function getAvailableParts(Request $request) { return $this->available($request); }
    public function getSummary() { return $this->stocksSummary(); }
    public function getList(Request $request) { return $this->stocksList($request); }
    public function getParts(Request $request) { return $this->stocksList($request); }
    public function getPartDetail(Request $request) { return $this->partDetail($request); }

    public function getConnectedSet(Request $request)
    {
        $sn = trim($request->input('sn', ''));
        if (!$sn) return $this->errorResponse('Nomor seri part diperlukan');
        $assy = DB::table('assemblies')
            ->where('part15_sn', $sn)
            ->orWhere('part1_sn', $sn)
            ->orWhere('part12_sn', $sn)
            ->first();
        return $this->successResponse($assy);
    }

    public function reRegister(Request $request)
    {
        $sn = trim($request->input('sn', ''));
        $operator = $request->input('operator', 'operator');
        $notes = $request->input('notes', '');
        if (!$sn) return $this->errorResponse('Nomor seri part diperlukan');
        
        DB::table('master_stocks')
            ->where('sn', $sn)
            ->update([
                'status' => 'Tersedia',
                'allocated_tray_id' => null,
                'used_at' => null,
                'last_reason' => $notes,
                'last_operator' => $operator,
                'updated_at' => now(),
            ]);
            
        return $this->successResponse(null, "Part {$sn} berhasil diregister ulang.");
    }

}

<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        

        // Mode uncapped secara default untuk memastikan seluruh data terpanggil tanpa batasan
        $limitParam = $request->input('limit');
        $uncapped = (!$limitParam || $limitParam === 'all' || $limitParam === '0' || $limitParam === '-1');
        $limit = $uncapped ? 50000 : max(1, (int)$limitParam);

        $offset = ($page - 1) * $limit;



        $query = DB::table('master_stocks as m')

            ->leftJoin('trays as t', function ($join) {

                $join->on('m.allocated_tray_id', '=', 't.id');

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

            if ($isReplaced) {
                $query->where(DB::raw('COALESCE(m.is_replaced_from_assy, 0)'), 1)
                      ->whereNotIn('m.status', ['Declined', 'Decline'])
                      ->where(function ($w) {
                          $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                      })
                      ->where(function ($w) {
                          $w->whereNull('a.verified_status')->orWhere('a.verified_status', '!=', 'DECLINED');
                      });
            } else {
                $query->where(DB::raw('COALESCE(m.is_replaced_from_assy, 0)'), 0);
            }

        }



        if ($status) {

            $statusLower = strtolower(trim($status));

            if (in_array($statusLower, ['part diganti', 'replaced'])) {

                $query->where(DB::raw('COALESCE(m.is_replaced_from_assy, 0)'), 1)
                      ->whereNotIn('m.status', ['Declined', 'Decline'])
                      ->where(function ($w) {
                          $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                      })
                      ->where(function ($w) {
                          $w->whereNull('a.verified_status')->orWhere('a.verified_status', '!=', 'DECLINED');
                      });

            } elseif (in_array($statusLower, ['decline', 'declined'])) {

                $query->where(function ($w) {

                    $w->where('a.is_declined', 1)

                      ->orWhere('a.verified_status', 'DECLINED')

                      ->orWhere('a.warehouse_receipt_status', 'DECLINED')

                      ->orWhereIn('m.status', ['Declined', 'Decline']);

                });

            } elseif ($statusLower === 'tersedia') {

                $query->where(function ($w) {
                    $w->where('m.status', 'Tersedia')
                      ->orWhere('t.status', 'returned')
                      ->orWhere('t.return_verified', 1);
                })
                ->where(function ($w) {
                    $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                })
                ->where(function ($w) {
                    $w->whereNull('a.verified_status')->orWhere('a.verified_status', '!=', 'DECLINED');
                })
                ->where(function ($w) {
                    $w->whereNull('a.warehouse_receipt_status')->orWhere('a.warehouse_receipt_status', '!=', 'DECLINED');
                })
                ->whereNotIn('m.status', ['Declined', 'Decline', 'Pending Verif Admin', 'Scrap']);

            } elseif ($statusLower === 'supplied') {

                $query->where(function ($w) {
                    $w->where(function ($inner) {
                        $inner->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('a.verified_status')->orWhere('a.verified_status', '!=', 'DECLINED');
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('a.warehouse_receipt_status')->orWhere('a.warehouse_receipt_status', '!=', 'DECLINED');
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('t.status')->orWhere('t.status', '!=', 'returned');
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('t.return_verified')->orWhere('t.return_verified', '!=', 1);
                    })
                    ->whereNotIn('m.status', ['Declined', 'Decline'])
                    ->where(function ($inner) {
                        $inner->where(DB::raw('COALESCE(a.status, m.status)'), 'Supplied')
                              ->orWhere('m.status', 'Supplied')
                              ->orWhere('m.status', 'Pending Verif Admin');
                    });
                });

            } elseif ($statusLower === 'good') {

                $query->where(function ($w) {
                    $w->where(function ($inner) {
                        $inner->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('a.verified_status')->orWhere('a.verified_status', '!=', 'DECLINED');
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('a.warehouse_receipt_status')->orWhere('a.warehouse_receipt_status', '!=', 'DECLINED');
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('t.status')->orWhere('t.status', '!=', 'returned');
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('t.return_verified')->orWhere('t.return_verified', '!=', 1);
                    })
                    ->whereNotIn('m.status', ['Declined', 'Decline', 'Pending Verif Admin', 'Tersedia'])
                    ->whereIn(DB::raw('COALESCE(a.status, m.status)'), ['Good', 'Done']);
                });

            } else {

                $query->where(function ($w) use ($status) {

                    $w->where(function ($inner) {

                        $inner->whereNull('a.is_declined')->orWhere('a.is_declined', 0);

                    })
                    ->where(function ($inner) {

                        $inner->whereNull('a.verified_status')->orWhere('a.verified_status', '!=', 'DECLINED');

                    })
                    ->where(function ($inner) {

                        $inner->whereNull('a.warehouse_receipt_status')->orWhere('a.warehouse_receipt_status', '!=', 'DECLINED');

                    })
                    ->whereNotIn('m.status', ['Declined', 'Decline', 'Pending Verif Admin'])
                    ->where(DB::raw('COALESCE(a.status, m.status, "Tersedia")'), $status);

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

            DB::raw('CASE WHEN (t.status = "returned" OR t.return_verified = 1) THEN NULL ELSE t.tray_code END as tray_code'),

            DB::raw('CASE WHEN (t.status = "returned" OR t.return_verified = 1) THEN NULL ELSE COALESCE(u_wh.full_name, t.pic_warehouse) END as pic_warehouse'),

            DB::raw('CASE WHEN (t.status = "returned" OR t.return_verified = 1) THEN NULL ELSE COALESCE(u_op.full_name, t.assigned_operator) END as assigned_operator'),

            DB::raw('CASE WHEN (t.status = "returned" OR t.return_verified = 1) THEN NULL ELSE t.source_document_mo END as source_document_mo'),

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
                WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN "Declined"
                WHEN m.status = "Pending Verif Admin" THEN "Pending Verif Admin"
                WHEN m.status = "Scrap" THEN "Scrap"
                WHEN m.status = "Tersedia" THEN "Tersedia"
                WHEN (t.status = "returned" OR t.return_verified = 1) THEN "Tersedia"
                WHEN COALESCE(m.is_replaced_from_assy, 0) = 1 THEN "Hold"
                WHEN COALESCE(a.status, m.status) IN ("Good", "Done") THEN "Good"
                ELSE COALESCE(a.status, m.status, "Tersedia")
            END as display_status'),

            DB::raw('CASE 
                WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN "Declined"
                WHEN m.status = "Pending Verif Admin" THEN "Pending Verif Admin"
                WHEN m.status = "Scrap" THEN "Scrap"
                WHEN m.status = "Tersedia" THEN "Tersedia"
                WHEN (t.status = "returned" OR t.return_verified = 1) THEN "Tersedia"
                WHEN COALESCE(m.is_replaced_from_assy, 0) = 1 THEN "Hold"
                WHEN COALESCE(a.status, m.status) IN ("Good", "Done") THEN "Good"
                ELSE COALESCE(a.status, m.status, "Tersedia")
            END as effective_status')

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

            ->leftJoin('trays as t', 'm.allocated_tray_id', '=', 't.id')

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

                DB::raw('COUNT(CASE WHEN (m.status = "Tersedia" OR t.status = "returned" OR t.return_verified = 1) AND (a.is_declined = 0 OR a.is_declined IS NULL) AND (a.warehouse_receipt_status IS NULL OR a.warehouse_receipt_status != "DECLINED") AND m.status NOT IN ("Declined", "Decline", "Pending Verif Admin", "Scrap") THEN 1 END) as available'),

                DB::raw('COUNT(CASE 
                    WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN NULL
                    WHEN m.status = "Pending Verif Admin" THEN 1
                    WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND COALESCE(a.status, m.status) = "Supplied" THEN 1 
                END) as allocated'),

                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN NULL WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND COALESCE(a.status, m.status) = "Done" THEN 1 END) as used'),

                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN NULL WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND COALESCE(a.status, m.status) IN ("Good", "Done") THEN 1 END) as good_count'),

                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN NULL WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND COALESCE(a.status, m.status) = "Hold" THEN 1 END) as hold_count'),

                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) = "Scrap" THEN 1 END) as scrap_count'),

                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN NULL WHEN m.status != "Tersedia" AND (t.status IS NULL OR (t.status != "returned" AND COALESCE(t.return_verified, 0) != 1)) AND COALESCE(a.status, m.status) = "Rework" THEN 1 END) as rework_count'),

                DB::raw('COUNT(CASE WHEN (a.is_declined = 1 OR a.verified_status = "DECLINED" OR a.warehouse_receipt_status = "DECLINED" OR m.status IN ("Declined", "Decline")) THEN 1 END) as decline_count'),

                DB::raw('COUNT(CASE WHEN COALESCE(m.is_replaced_from_assy, 0) = 1 AND m.status NOT IN ("Declined", "Decline") AND COALESCE(a.is_declined, 0) = 0 AND COALESCE(a.verified_status, "") != "DECLINED" AND COALESCE(a.warehouse_receipt_status, "") != "DECLINED" THEN 1 END) as replaced_count'),

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

        $partType = $request->input('type') ?: $request->input('part_type');

        $q = trim((string)$request->input('q', ''));

        $limitParam = $request->input('limit');



        $query = MasterStock::query();



        if ($partType) {

            $query->where('part_type', $partType);

        }



        $query->where('status', 'Tersedia')

            ->whereNull('allocated_tray_id');



        // Jika tray_id diberikan, kecualikan part yang sedang aktif digunakan pada tray yang sama

        $trayId = $request->input('tray_id') ?: $request->input('exclude_tray_id');

        if ($trayId) {

            $usedSnsInTray = DB::table('assemblies')

                ->where('tray_id', $trayId)

                ->where(function ($w) {

                    $w->whereNull('is_reregistered')->orWhere('is_reregistered', 0);

                })

                ->get(['part15_sn', 'part1_sn', 'part12_sn']);

            $usedSns = [];

            foreach ($usedSnsInTray as $row) {

                if ($row->part15_sn && $row->part15_sn !== '-') $usedSns[] = $row->part15_sn;

                if ($row->part1_sn && $row->part1_sn !== '-') $usedSns[] = $row->part1_sn;

                if ($row->part12_sn && $row->part12_sn !== '-') $usedSns[] = $row->part12_sn;

            }

            if (!empty($usedSns)) {

                $query->whereNotIn('sn', array_unique($usedSns));

            }

        }



        if ($q !== '') {

            $qClean = str_replace(['.', ',', ' '], '', $q);

            $query->where(function ($w) use ($q, $qClean) {

                $w->where('sn', 'like', "{$q}%");

                if ($qClean !== '' && $qClean !== $q) {

                    $w->orWhere('sn', 'like', "{$qClean}%");

                }

                $w->orWhere('sn', 'like', "%{$q}%");

                if ($qClean !== '' && $qClean !== $q) {

                    $w->orWhere('sn', 'like', "%{$qClean}%");

                }

            });

        }



        $query->orderBy('sn', 'asc');

        // ATURAN BAKU: Data ketersediaan Master Stok tidak boleh dibatasi (Uncapped)
        // Seluruh data yang memenuhi kriteria status Tersedia wajib dipanggil lengkap tanpa batasan.
        $items = $query->select('sn', 'cs', 'part_type', 'status')->get();

        return $this->successResponse($items);

    }



    /**

     * Detail pelacakan part (Traceability Drilldown)

     */

    public function partDetail(Request $request)

    {

        $sn = trim($request->input('sn', ''));

        $partType = trim((string)$request->input('part_type', ''));

        if (!$sn) {

            return $this->errorResponse('Nomor seri part diperlukan');

        }



        $cleanDot = str_replace('.', '', $sn);

        $query = MasterStock::where(function ($w) use ($sn, $cleanDot) {

            $w->where('sn', $sn)

              ->orWhere(DB::raw("REPLACE(sn, '.', '')"), $cleanDot);

        });

        if ($partType) {

            $query->where('part_type', $partType);

        }

        $stock = $query->first();



        if (!$stock) {

            return $this->errorResponse('Data part tidak ditemukan', 404);

        }



        // Relasi Tray & Assembly HANYA diambil jika part sedang AKTIF teralokasi (bukan Tersedia / bukan detached)

        $tray = null;

        $assembly = null;



        if ($stock->status !== 'Tersedia' && !empty($stock->allocated_tray_id)) {

            $tray = DB::table('trays')->where('id', $stock->allocated_tray_id)->first();

            $assembly = DB::table('assemblies')

                ->where('tray_id', $stock->allocated_tray_id)

                ->where(function ($w) use ($sn) {

                    $w->where('part15_sn', $sn)

                      ->orWhere('part1_sn', $sn)

                      ->orWhere('part12_sn', $sn);

                })

                ->orderBy('id', 'desc')

                ->first();

        }

        // Jika part tidak sedang aktif di baki, cari jejak perakitan terakhir dari assemblies atau history untuk rincian unit
        if (!$assembly) {
            $assembly = DB::table('assemblies')
                ->where(function ($w) use ($sn) {
                    $w->where('part15_sn', $sn)
                      ->orWhere('part1_sn', $sn)
                      ->orWhere('part12_sn', $sn);
                })
                ->orderBy('id', 'desc')
                ->first();

            if (!$assembly && \Illuminate\Support\Facades\Schema::hasTable('assembly_wo_history')) {
                $assembly = DB::table('assembly_wo_history')
                    ->where(function ($w) use ($sn) {
                        $w->where('part15_sn', $sn)
                          ->orWhere('part1_sn', $sn)
                          ->orWhere('part12_sn', $sn);
                    })
                    ->orderBy('id', 'desc')
                    ->first();
            }

            // Jika masih belum ditemukan, cek apakah part ini pernah diganti dari suatu set perakitan (part_replacement_requests)
            if (!$assembly) {
                $repl = DB::table('part_replacement_requests')
                    ->where(function ($w) use ($sn) {
                        $w->where('replacements_json', 'LIKE', '%"old_sn":"' . $sn . '"%')
                          ->orWhere('replacements_json', 'LIKE', '%"new_sn":"' . $sn . '"%');
                    })
                    ->orderBy('id', 'desc')
                    ->first();

                if ($repl) {
                    $origAssy = DB::table('assemblies')->where('id', $repl->assembly_id)->first();
                    if (!$origAssy && \Illuminate\Support\Facades\Schema::hasTable('assembly_wo_history')) {
                        $origAssy = DB::table('assembly_wo_history')->where('id', $repl->assembly_id)->first();
                    }
                    if ($origAssy) {
                        $assembly = clone $origAssy;
                        $assembly->is_historical_replacement = true;
                        // Pasang kembali nomor SN part ini ke field perakitannya agar pasangan part saat dirakit tampil akurat
                        if ($stock->part_type === '15') {
                            $assembly->part15_sn = $sn;
                        } elseif ($stock->part_type === '1') {
                            $assembly->part1_sn = $sn;
                        } elseif ($stock->part_type === '12') {
                            $assembly->part12_sn = $sn;
                        }
                        if (!empty($repl->reason)) {
                            $assembly->reason = $repl->reason;
                        }
                        if (!$tray && (!empty($repl->tray_id) || !empty($assembly->tray_id))) {
                            $tray = DB::table('trays')->where('id', $repl->tray_id ?: $assembly->tray_id)->first();
                        }
                    }
                }
            }

            if ($assembly && !$tray && !empty($assembly->tray_id)) {
                $tray = DB::table('trays')->where('id', $assembly->tray_id)->first();
            }
        }

        if (!$tray && !empty($stock->last_tray_id)) {
            $tray = DB::table('trays')->where('id', $stock->last_tray_id)->first();
        }

        // Format nama personil/operator agar mudah dan jelas dibaca
        if ($assembly) {
            $assyPic = $assembly->pic_assembly ?? null;
            $pic = $assyPic ? DB::table('users')->where('username', $assyPic)->value('full_name') : null;
            $assembly->pic_assembly_name = $pic ?: $assyPic;
            $assyTester = $assembly->assy_tester_username ?? null;
            $tester = $assyTester ? DB::table('users')->where('username', $assyTester)->value('full_name') : null;
            $assembly->assy_tester_fullname = $tester ?: $assyTester;
        }
        if ($tray) {
            $whU = $tray->pic_warehouse ?? null;
            $whUser = $whU ? DB::table('users')->where('username', $whU)->value('full_name') : null;
            $tray->pic_warehouse_name = $whUser ?: $whU;
            $opU = $tray->assigned_operator ?? null;
            $opUser = $opU ? DB::table('users')->where('username', $opU)->value('full_name') : null;
            $tray->assigned_operator_name = $opUser ?: $opU;
        }



        // 1. Cek informasi pengiriman jika Part 15 (Final Unit SN)

        $shipping = null;

        if ($stock->part_type === '15') {

            $shipping = DB::table('packing_list_items as pli')

                ->join('delivery_orders as do', 'pli.delivery_order_id', '=', 'do.id')

                ->leftJoin('packing_list_boxes as b', 'pli.box_id', '=', 'b.id')

                ->leftJoin('packing_lists as pl', 'b.packing_list_id', '=', 'pl.id')

                ->where('pli.serial_number', $sn)

                ->select([

                    'do.id as delivery_order_id',

                    'do.do_number',

                    'do.pl_number',

                    'do.do_date',

                    'do.customer_name',

                    'do.status as do_status',

                    'pl.crate_number',

                    'b.box_number',

                    'pli.slot_number',

                    'pli.created_at as packed_at',

                ])

                ->first();

        }



        // 2. Tentukan Milestone / Lifecycle Stage secara presisi dan mudah dibaca

        $lifecycle = [

            'stage_key' => 'TERSEDIA',

            'stage_title' => 'Stok Tersedia di Gudang (Bebas / Steril)',

            'badge_color' => 'emerald',

            'current_location' => 'Gudang Komponen',

            'active_doc' => null,

            'description' => 'Komponen steril tanpa ikatan baki, siap dialokasikan ke Tray baru.'

        ];



        if ($stock->status === 'Tersedia') {

            $lifecycle['stage_key'] = 'TERSEDIA';

            $lifecycle['stage_title'] = 'Stok Tersedia di Gudang (Bebas / Steril)';

            $lifecycle['badge_color'] = 'emerald';

            $lifecycle['current_location'] = 'Gudang Komponen';

            $lifecycle['description'] = 'Komponen steril tanpa ikatan baki, siap dialokasikan ke Tray baru.';

        } elseif ($stock->is_replaced_from_assy == 1 || $stock->status === 'Part Diganti' || $stock->status === 'Hold') {

            $lifecycle['stage_key'] = 'HOLD';

            $lifecycle['stage_title'] = 'Part Dilepas dari Perakitan (Status Hold)';

            $lifecycle['badge_color'] = 'amber';

            $lifecycle['current_location'] = 'Karantina Material / Evaluasi Admin';

            $lifecycle['description'] = 'Part telah dilepas dari set rakitan asalnya dan berstatus Hold.';

        } elseif ($stock->status === 'Scrap') {

            $lifecycle['stage_key'] = 'SCRAP';

            $lifecycle['stage_title'] = 'Material Rusak Permanen (Scrap)';

            $lifecycle['badge_color'] = 'rose';

            $lifecycle['current_location'] = 'Area Scrap / Pemusnahan';

            $lifecycle['description'] = 'Part tidak dapat digunakan kembali dalam proses produksi.';

        } elseif ($shipping) {

            $lifecycle['stage_key'] = 'SHIPPED';

            $lifecycle['stage_title'] = "Terkirim ke Customer ({$shipping->customer_name})";

            $lifecycle['badge_color'] = 'blue';

            $lifecycle['current_location'] = "Surat Jalan: {$shipping->do_number}";

            $lifecycle['active_doc'] = $shipping->do_number;

            $lifecycle['description'] = "Dikemas di Peti #{$shipping->crate_number}, Box #{$shipping->box_number}, Slot #{$shipping->slot_number} (Tgl: {$shipping->do_date}).";

        } elseif ($assembly && in_array(strtoupper($assembly->warehouse_receipt_status ?? ''), ['ACCEPTED', 'ACC'])) {

            $tray = $tray ?: ($assembly && !empty($assembly->tray_id) ? DB::table('trays')->where('id', $assembly->tray_id)->first() : null);

            $cleanSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);

            $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));

            $fallbackGR = $isPU && preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $pm) ? "WH/GR/PU/{$pm[1]}" : "WH/GR/{$cleanSeq}";

            $docNum = $assembly->warehouse_doc_number ?: $fallbackGR;

            $lifecycle['stage_key'] = 'WAREHOUSE_ACCEPTED';

            $lifecycle['stage_title'] = 'Diterima Fisik Gudang Barang Jadi (Siap Kirim)';

            $lifecycle['badge_color'] = 'emerald';

            $lifecycle['current_location'] = 'Gudang Barang Jadi';

            $lifecycle['active_doc'] = $docNum;

            $lifecycle['description'] = "Fisik unit telah di-ACC oleh Gudang (Dokumen: {$docNum}). Siap diterbitkan Surat Jalan.";

        } elseif ($assembly && in_array(strtoupper($assembly->qc_status ?? ''), ['GOOD', 'GOOD_QC'])) {

            $tray = $tray ?: ($assembly && !empty($assembly->tray_id) ? DB::table('trays')->where('id', $assembly->tray_id)->first() : null);

            $cleanSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);

            $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));

            $fallbackQC = $isPU && preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $pm) ? "WH/QC/PU/{$pm[1]}" : "WH/QC/{$cleanSeq}";

            $docNum = $assembly->qc_doc_number ?: $fallbackQC;

            $lifecycle['stage_key'] = 'QC_GOOD';

            $lifecycle['stage_title'] = 'Lolos Uji Mutu QC (Menunggu Penerimaan Gudang)';

            $lifecycle['badge_color'] = 'cyan';

            $lifecycle['current_location'] = 'Area Quality Control';

            $lifecycle['active_doc'] = $docNum;

            $lifecycle['description'] = "Lolos inspeksi mutu QC (Dokumen: {$docNum}). Menunggu verifikasi fisik oleh Gudang.";

        } elseif ($assembly && strtoupper($assembly->qc_status ?? '') === 'HOLD_QC') {

            $lifecycle['stage_key'] = 'QC_HOLD';

            $lifecycle['stage_title'] = 'Tertahan di Quality Control (Hold QC)';

            $lifecycle['badge_color'] = 'amber';

            $lifecycle['current_location'] = 'Area Karantina QC';

            $lifecycle['active_doc'] = $assembly->qc_doc_number;

            $lifecycle['description'] = "Tertahan inspeksi QC dengan alasan: " . ($assembly->qc_failure_reasons ?: 'Kendala mutu fisik');

        } elseif ($assembly && strtoupper($assembly->assy_test_status ?? '') === 'GOOD') {

            $tray = $tray ?: ($assembly && !empty($assembly->tray_id) ? DB::table('trays')->where('id', $assembly->tray_id)->first() : null);

            $cleanSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);

            $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));

            if ($isPU) {

                preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);

                $puNum = !empty($m[1]) ? (int)$m[1] : 1;

                $fallbackTF1 = "ASSY/QC/PU/{$puNum}";

            } else {

                $fallbackTF1 = "ASSY/QC/{$cleanSeq}";

            }

            $docNum = $assembly->assy_doc_number ?: $fallbackTF1;

            $lifecycle['stage_key'] = 'TF1_GOOD';

            $lifecycle['stage_title'] = 'Lolos Uji Fungsional 1 (Menunggu Inspeksi QC)';

            $lifecycle['badge_color'] = 'indigo';

            $lifecycle['current_location'] = 'Antrean Quality Control (TF 2)';

            $lifecycle['active_doc'] = $docNum;

            $lifecycle['description'] = "17 parameter checklist TF 1 lulus (Dokumen: {$docNum}). Siap diuji QC.";

        } elseif ($assembly && (str_contains(strtoupper($assembly->assy_test_status ?? ''), 'HOLD') || in_array(strtoupper($assembly->assy_test_status ?? ''), ['NOT GOOD', 'FAIL']))) {

            $lifecycle['stage_key'] = 'TF1_HOLD';

            $lifecycle['stage_title'] = 'Tertahan di Uji Fungsional 1 (TF 1 Hold)';

            $lifecycle['badge_color'] = 'amber';

            $lifecycle['current_location'] = 'Meja Kerja Assembly';

            $lifecycle['description'] = 'Ada parameter checklist TF 1 yang belum sesuai. Memerlukan single retest atau perbaikan fisik.';

        } elseif ($assembly && $assembly->finished_at) {

            $lifecycle['stage_key'] = 'ASSEMBLED';

            $lifecycle['stage_title'] = "Selesai Dirakit di Set #{$assembly->set_number} (Menunggu TF 1)";

            $lifecycle['badge_color'] = 'violet';

            $lifecycle['current_location'] = "Tray " . ($tray?->tray_code ?: '-') . " (Slot #{$assembly->set_number})";

            $lifecycle['description'] = 'Perakitan selesai. Menunggu pengujian fungsional 17 parameter checklist.';

        } elseif ($tray) {

            $lifecycle['stage_key'] = 'SUPPLIED';

            $lifecycle['stage_title'] = "Dialokasikan ke Tray {$tray->tray_code}";

            $lifecycle['badge_color'] = 'sky';

            $lifecycle['current_location'] = "Baki Produksi {$tray->tray_code}";

            $lifecycle['description'] = "Ditugaskan ke operator: " . ($tray->assigned_operator ?: 'Belum ditentukan') . " (MO: " . ($tray->source_document_mo ?: '-') . ")";

        }



        $rawAuditLogs = DB::table('part_audit_logs')
            ->leftJoin('users', 'part_audit_logs.actor_username', '=', 'users.username')
            ->where(function ($w) use ($sn) {
                $w->where('part_audit_logs.sn', $sn);
                $clean = ltrim($sn, '0');
                if (!empty($clean) && $clean !== $sn) {
                    $w->orWhere('part_audit_logs.sn', $clean);
                }
            })
            ->select([
                'part_audit_logs.*',
                DB::raw("COALESCE(users.full_name, part_audit_logs.actor_username) as actor_full_name")
            ])
            ->orderBy('part_audit_logs.created_at', 'desc')
            ->get();

        $trayAssyOperator = $tray?->assigned_operator 
            ?: ($assembly?->pic_assembly 
            ?: ($stock?->last_operator ?: 'Administrator NKRI'));
        $trayWhOperator = $tray?->pic_warehouse ?: 'Administrator NKRI';
        $usersMap = DB::table('users')->pluck('full_name', 'username')->toArray();

        foreach ($rawAuditLogs as $log) {
            $actUser = trim((string)$log->actor_username);
            $cleanUser = strtolower($actUser);

            if (empty($actUser) || in_array($cleanUser, ['operator', 'gudang', 'tester', '-', 'null', 'undefined'])) {
                if ($log->actor_role === 'gudang') {
                    $resolvedName = $trayWhOperator;
                } elseif ($log->actor_role === 'tester') {
                    $resolvedName = $assembly?->assy_tester_fullname ?: ($assembly?->assy_tester_username ?: 'Administrator NKRI');
                } elseif ($log->actor_role === 'qc') {
                    $resolvedName = $assembly?->qc_tested_by ?: 'Administrator NKRI';
                } else {
                    $resolvedName = $trayAssyOperator;
                }
            } else {
                $resolvedName = $usersMap[$actUser] ?? ($actUser === 'admin' ? 'Administrator NKRI' : $actUser);
            }

            $log->actor_full_name = $resolvedName;
            $log->actor_username = $resolvedName;
        }

        $mergedLogs = collect($rawAuditLogs);

        // Tambahkan jejak milestone siklus hidup aktual agar riwayat part selalu komprehensif
        if ($stock && $stock->created_at) {
            $regUser = $stock->last_operator ?: 'admin';
            $regName = $stock->last_operator ? (DB::table('users')->where('username', $stock->last_operator)->value('full_name') ?: $stock->last_operator) : 'Administrator NKRI';
            $mergedLogs->push((object)[
                'id' => 'milestone_reg_' . $stock->id,
                'created_at' => (string)$stock->created_at,
                'action_type' => 'REGISTRASI_MASTER',
                'old_status' => '-',
                'new_status' => 'Tersedia',
                'reason' => 'Registrasi nomor seri ke Master Stok Gudang',
                'actor_username' => $regUser,
                'actor_full_name' => $regName,
                'actor_role' => 'gudang'
            ]);
        }

        if ($tray && $tray->created_at) {
            $supplyUser = $tray->pic_warehouse ?: 'admin';
            $supplyName = $tray->pic_warehouse_name ?: (DB::table('users')->where('username', $supplyUser)->value('full_name') ?: ($supplyUser === 'admin' ? 'Administrator NKRI' : $supplyUser));
            $mergedLogs->push((object)[
                'id' => 'milestone_supply_' . $tray->id,
                'created_at' => (string)$tray->created_at,
                'action_type' => 'SUPPLY_TRAY',
                'old_status' => 'Tersedia',
                'new_status' => 'Supplied',
                'reason' => "Dialokasikan ke Tray {$tray->tray_code}" . ($tray->source_document_mo ? " (MO: {$tray->source_document_mo})" : ''),
                'actor_username' => $supplyUser,
                'actor_full_name' => $supplyName,
                'actor_role' => 'gudang'
            ]);
        }

        if ($assembly && !empty($assembly->finished_at)) {
            $assyUser = ($assembly->pic_assembly ?? null) ?: (($tray->assigned_operator ?? null) ?: 'operator');
            $assyName = ($assembly->pic_assembly_name ?? null) ?: (DB::table('users')->where('username', $assyUser)->value('full_name') ?: ($assyUser === 'admin' ? 'Administrator NKRI' : $assyUser));
            $mergedLogs->push((object)[
                'id' => 'milestone_assy_' . $assembly->id,
                'created_at' => (string)$assembly->finished_at,
                'action_type' => 'PERAKITAN_SET',
                'old_status' => 'Supplied',
                'new_status' => ($assembly->status ?? null) ?: 'Good',
                'reason' => "Selesai dirakit di Set #{$assembly->set_number} (" . (($assembly->status ?? null) ?: 'Good') . ")",
                'actor_username' => $assyUser,
                'actor_full_name' => $assyName,
                'actor_role' => 'assembly'
            ]);
        }

        if ($assembly && !empty($assembly->assy_tested_at)) {
            $testRow = DB::table('assembly_tests')->where('assembly_id', $assembly->id)->orWhere(function($q) use ($assembly) {
                $q->where('tray_id', $assembly->tray_id)->where('set_number', $assembly->set_number);
            })->first();
            $tf1User = $testRow?->tester_username ?: (($assembly->assy_tester_username ?? null) ?: 'admin');
            $tf1Name = ($assembly->assy_tester_fullname ?? null) ?: (DB::table('users')->where('username', $tf1User)->value('full_name') ?: ($tf1User === 'admin' ? 'Administrator NKRI' : $tf1User));
            $mergedLogs->push((object)[
                'id' => 'milestone_tf1_' . $assembly->id,
                'created_at' => (string)$assembly->assy_tested_at,
                'action_type' => 'TEST_FUNCTIONAL_1',
                'old_status' => ($assembly->status ?? null) ?: 'Good',
                'new_status' => ($assembly->assy_test_status ?? null) ?: 'Good',
                'reason' => "Uji Fungsional 1 (" . (($assembly->assy_test_status ?? null) ?: 'Good') . ")" . (!empty($assembly->assy_doc_number) ? " - Dokumen: {$assembly->assy_doc_number}" : ''),
                'actor_username' => $tf1User,
                'actor_full_name' => $tf1Name,
                'actor_role' => 'tester'
            ]);
        }

        if ($assembly && !empty($assembly->qc_tested_at)) {
            $qcUser = ($assembly->qc_inspector_username ?? null) ?: (($assembly->qc_tested_by ?? null) ?: 'admin');
            $qcName = DB::table('users')->where('username', $qcUser)->value('full_name') ?: ($qcUser === 'admin' ? 'Administrator NKRI' : $qcUser);
            $mergedLogs->push((object)[
                'id' => 'milestone_qc_' . $assembly->id,
                'created_at' => (string)$assembly->qc_tested_at,
                'action_type' => 'TEST_FUNCTIONAL_2_QC',
                'old_status' => ($assembly->assy_test_status ?? null) ?: 'Good',
                'new_status' => ($assembly->qc_status ?? null) ?: 'GOOD',
                'reason' => "Inspeksi Mutu QC (" . (($assembly->qc_status ?? null) ?: 'GOOD') . ")" . (!empty($assembly->qc_doc_number) ? " - Dokumen: {$assembly->qc_doc_number}" : ''),
                'actor_username' => $qcUser,
                'actor_full_name' => $qcName,
                'actor_role' => 'qc'
            ]);
        }

        if ($assembly && !empty($assembly->warehouse_receipt_at)) {
            $grUser = ($assembly->warehouse_receipt_by ?? null) ?: (($tray->verified_by_user ?? null) ?: (($tray->return_verified_by ?? null) ?: 'admin'));
            $grName = DB::table('users')->where('username', $grUser)->value('full_name') ?: ($grUser === 'admin' ? 'Administrator NKRI' : $grUser);
            $mergedLogs->push((object)[
                'id' => 'milestone_gr_' . $assembly->id,
                'created_at' => (string)$assembly->warehouse_receipt_at,
                'action_type' => 'PENERIMAAN_GUDANG',
                'old_status' => ($assembly->qc_status ?? null) ?: 'GOOD',
                'new_status' => ($assembly->warehouse_receipt_status ?? null) ?: 'ACCEPTED',
                'reason' => "Verifikasi Fisik Gudang (" . (($assembly->warehouse_receipt_status ?? null) ?: 'ACC') . ")" . (!empty($assembly->warehouse_doc_number) ? " - Dokumen: {$assembly->warehouse_doc_number}" : ''),
                'actor_username' => $grUser,
                'actor_full_name' => $grName,
                'actor_role' => 'gudang'
            ]);
        }

        if ($shipping && $shipping->packed_at) {
            $doUser = $shipping->packed_by ?: 'admin';
            $doName = DB::table('users')->where('username', $doUser)->value('full_name') ?: ($doUser === 'admin' ? 'Administrator NKRI' : $doUser);
            $mergedLogs->push((object)[
                'id' => 'milestone_do_' . $shipping->delivery_order_id,
                'created_at' => (string)$shipping->packed_at,
                'action_type' => 'DELIVERY_ORDER',
                'old_status' => 'ACCEPTED',
                'new_status' => 'SHIPPED',
                'reason' => "Dikemas untuk Surat Jalan {$shipping->do_number} ke {$shipping->customer_name}",
                'actor_username' => $doUser,
                'actor_full_name' => $doName,
                'actor_role' => 'gudang'
            ]);
        }

        // Milestone Pelepasan / Penggantian Part jika pernah diganti dari rakitan
        $replLog = DB::table('part_replacement_requests')
            ->where(function ($w) use ($sn) {
                $w->where('replacements_json', 'LIKE', '%"old_sn":"' . $sn . '"%')
                  ->orWhere('replacements_json', 'LIKE', '%"new_sn":"' . $sn . '"%');
            })
            ->orderBy('id', 'desc')
            ->first();

        if ($replLog) {
            $isOld = str_contains($replLog->replacements_json, '"old_sn":"' . $sn . '"');
            $actType = $isOld ? 'PELEPASAN_PART' : 'PENGGANTIAN_PART_BARU';
            $oldSt = $isOld ? 'Supplied' : 'Tersedia';
            $newSt = $isOld ? ($replLog->old_part_status ?: 'Hold') : 'Supplied';
            $rsn = $isOld ? "Dilepas dari Set #{$replLog->set_number}: {$replLog->reason}" : "Dipasang ke Set #{$replLog->set_number} sebagai part pengganti";
            $actorU = $replLog->admin_username ?: ($replLog->requestor_username ?: 'admin');
            $actorName = DB::table('users')->where('username', $actorU)->value('full_name') ?: ($actorU === 'admin' ? 'Administrator NKRI' : $actorU);

            $mergedLogs->push((object)[
                'id' => 'milestone_repl_' . $replLog->id,
                'created_at' => (string)($replLog->verified_at ?: $replLog->created_at),
                'action_type' => $actType,
                'old_status' => $oldSt,
                'new_status' => $newSt,
                'reason' => $rsn,
                'actor_username' => $actorU,
                'actor_full_name' => $actorName,
                'actor_role' => 'admin'
            ]);
        }

        $auditLogs = $mergedLogs->sortByDesc('created_at')->values()->all();

        $reRegisterRequests = DB::table('re_register_requests')
            ->leftJoin('users as u_req', 're_register_requests.requestor_username', '=', 'u_req.username')
            ->leftJoin('users as u_adm', 're_register_requests.admin_username', '=', 'u_adm.username')
            ->where('re_register_requests.sn', $sn)
            ->select([
                're_register_requests.*',
                DB::raw("COALESCE(u_req.full_name, re_register_requests.requestor_username) as requestor_full_name"),
                DB::raw("COALESCE(u_adm.full_name, re_register_requests.admin_username) as admin_full_name")
            ])
            ->orderBy('re_register_requests.created_at', 'desc')
            ->get();

        return $this->successResponse([
            'stock' => $stock,
            'tray' => $tray,
            'assembly' => $assembly,
            'shipping' => $shipping,
            'lifecycle' => $lifecycle,
            'audit_logs' => $auditLogs,
            're_register_requests' => $reRegisterRequests,
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
                'error' => "Part {$partType} dengan nomor '{$cleanSn}' sudah terdaftar di Master Stok (Status: {$existing->status}). Transaksi dibatalkan.",
                'existing_part' => [
                    'id' => $existing->id,
                    'part_type' => $existing->part_type,
                    'sn' => $existing->sn,
                    'status' => $existing->status,
                    'cs' => $existing->cs ?? null,
                    'created_at' => $existing->created_at ?? null,
                ]
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


    /**
     * Preview Cek Ketersediaan Bulk Part Number (Mencegah Duplikasi & Preview Part yang Sudah Ada)
     * Endpoint: POST /api/master/check-bulk
     */
    public function checkBulk(Request $request)
    {
        $partType = trim((string)$request->input('part_type', ''));
        if (!in_array($partType, ['1', '12', '15'])) {
            return $this->errorResponse("Part Type harus '1', '12', atau '15'", 400);
        }

        $snStart = trim((string)$request->input('sn_start', ''));
        $snEnd = trim((string)$request->input('sn_end', ''));
        $snsRaw = $request->input('sns') ?: $request->input('sns_text');

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
                $lines = preg_split('/[

,]+/', $snsRaw, -1, PREG_SPLIT_NO_EMPTY);
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
            return response()->json(['ok' => true, 'data' => ['total' => 0, 'duplicates' => [], 'new_sns' => [], 'can_submit' => false]]);
        }

        if (empty($targetSns)) {
            return response()->json(['ok' => true, 'data' => ['total' => 0, 'duplicates' => [], 'new_sns' => [], 'can_submit' => false]]);
        }

        // Query existing parts
        $existingRows = DB::table('master_stocks')
            ->where('part_type', $partType)
            ->where(function ($w) use ($targetSns) {
                $w->whereIn('sn', $targetSns)
                  ->orWhereIn(DB::raw("REPLACE(sn, '.', '')"), array_map(fn($s) => str_replace('.', '', $s), $targetSns));
            })
            ->get(['id', 'part_type', 'sn', 'status', 'created_at']);

        $existingSnMap = [];
        foreach ($existingRows as $row) {
            $existingSnMap[$row->sn] = $row;
            $existingSnMap[str_replace('.', '', $row->sn)] = $row;
        }

        $duplicates = [];
        $newSns = [];

        foreach ($targetSns as $sn) {
            $cSn = str_replace('.', '', $sn);
            if (isset($existingSnMap[$sn]) || isset($existingSnMap[$cSn])) {
                $row = $existingSnMap[$sn] ?? $existingSnMap[$cSn];
                $duplicates[] = [
                    'id' => $row->id,
                    'part_type' => $row->part_type,
                    'sn' => $sn,
                    'status' => $row->status,
                    'created_at' => $row->created_at,
                ];
            } else {
                $newSns[] = $sn;
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'part_type' => $partType,
                'total' => count($targetSns),
                'duplicates' => $duplicates,
                'duplicate_count' => count($duplicates),
                'new_sns' => $newSns,
                'new_count' => count($newSns),
                'can_submit' => (count($duplicates) === 0 && count($newSns) > 0),
            ]
        ]);
    }

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

        // 1. Strict Atomic Pre-Check: Deteksi apakah ada part yang sudah ada di master_stocks
        // Sesuai aturan: jika ada 1 saja yang sudah ada, transaksi BATAL TOTAL (0 part ditambahkan)
        $existingRows = DB::table('master_stocks')
            ->where('part_type', $partType)
            ->where(function ($w) use ($targetSns) {
                $w->whereIn('sn', $targetSns)
                  ->orWhereIn(DB::raw("REPLACE(sn, '.', '')"), array_map(fn($s) => str_replace('.', '', $s), $targetSns));
            })
            ->get(['id', 'part_type', 'sn', 'status']);

        if ($existingRows->isNotEmpty()) {
            $duplicateSns = $existingRows->pluck('sn')->unique()->values()->all();
            $dupCount = count($duplicateSns);
            $dupListStr = implode(', ', array_slice($duplicateSns, 0, 10));
            if ($dupCount > 10) {
                $dupListStr .= " dan " . ($dupCount - 10) . " lainnya";
            }

            return response()->json([
                'ok' => false,
                'error' => "Transaksi dibatalkan: Ditemukan {$dupCount} part yang sudah ada di Master Stock: [{$dupListStr}]. Seluruh transaksi dibatalkan (0 part ditambahkan).",
                'data' => [
                    'part_type' => $partType,
                    'total_submitted' => count($targetSns),
                    'duplicate_count' => $dupCount,
                    'duplicates' => $existingRows->map(fn($r) => ['sn' => $r->sn, 'status' => $r->status])->values()->all(),
                    'added_count' => 0,
                    'added' => [],
                ]
            ], 422);
        }

        // 2. Jika 100% bersih (0 duplikat), masukkan seluruh data secara atomik
        $added = [];
        DB::beginTransaction();
        try {
            foreach ($targetSns as $itemSn) {
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

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => "Berhasil menambahkan seluruh " . count($added) . " part baru ke Master Stok.",
                'data' => [
                    'part_type' => $partType,
                    'added_count' => count($added),
                    'duplicate_count' => 0,
                    'added' => $added,
                    'duplicates' => [],
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
        $limitParam = $request->input('limit');
        $uncapped = (!$limitParam || $limitParam === 'all' || $limitParam === '0' || $limitParam === '-1');
        $partType = trim((string)$request->input('part_type', '')) ?: trim((string)$request->input('type', ''));



        $latestAudit = DB::table('part_audit_logs')

            ->select('sn', 'part_type', DB::raw('MAX(id) as max_audit_id'))

            ->where('action_type', 'ADD_PART_NUMBER')

            ->groupBy('sn', 'part_type');



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

            ->leftJoinSub($latestAudit, 'la', function ($join) {

                $join->on('m.sn', '=', 'la.sn')

                     ->on('m.part_type', '=', 'la.part_type');

            })

            ->leftJoin('part_audit_logs as pal', 'la.max_audit_id', '=', 'pal.id');



        if ($partType) {

            $query->where('m.part_type', $partType);

        }



        if (!$uncapped && is_numeric($limitParam) && (int)$limitParam > 0) {
            $query->limit((int)$limitParam);
        }

        $rows = $query->orderBy('m.id', 'desc')->get();



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



        $stock = DB::table('master_stocks')->where('sn', $sn)->first();

        if (!$stock || $stock->status === 'Tersedia' || empty($stock->allocated_tray_id)) {

            // Part berstatus Tersedia atau tidak memiliki alokasi aktif -> BEBAS DARI RELASI APAPUN

            return $this->successResponse(null);

        }



        // HANYA ambil rakitan pada tray yang sedang aktif teralokasi

        $assy = DB::table('assemblies')

            ->where('tray_id', $stock->allocated_tray_id)

            ->where(function ($w) use ($sn) {

                $w->where('part15_sn', $sn)

                  ->orWhere('part1_sn', $sn)

                  ->orWhere('part12_sn', $sn);

            })

            ->where(function ($w) {

                $w->whereNull('is_reregistered')->orWhere('is_reregistered', 0);

            })

            ->first();



        return $this->successResponse($assy);

    }



    public function reRegister(Request $request)
    {
        $sn = trim($request->input('sn', ''));
        $operator = $request->input('operator', 'operator');
        $notes = $request->input('notes', '');

        if (!$sn) return $this->errorResponse('Nomor seri part diperlukan');

        $stock = DB::table('master_stocks')->where('sn', $sn)->first();
        if (!$stock) return $this->errorResponse("Part {$sn} tidak ditemukan", 404);

        $oldStatus = $stock->status;

        // Flow perbaikan: Part lepasan TIDAK boleh langsung menjadi Tersedia!
        // Wajib masuk ke antrean verifikasi Admin (Pending Verif Admin)
        DB::table('master_stocks')
            ->where('sn', $sn)
            ->update([
                'status' => 'Pending Verif Admin',
                'last_reason' => $notes ?: 'Pengajuan daftar ulang part ke stok Tersedia',
                'last_operator' => $operator,
                'updated_at' => now(),
            ]);

        // Buat atau perbarui catatan permohonan verifikasi ke Admin
        DB::table('re_register_requests')
            ->updateOrInsert(
                [
                    'sn' => $sn,
                    'part_type' => $stock->part_type,
                    'status' => 'PENDING',
                ],
                [
                    'current_status' => $oldStatus,
                    'target_status' => 'Tersedia',
                    'requestor_username' => $operator,
                    'requestor_role' => auth()->user()?->role ?: 'operator',
                    'reason' => $notes ?: 'Pengajuan daftar ulang part ke stok Tersedia',
                    'admin_username' => null,
                    'admin_notes' => null,
                    'approved_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

        PartAuditLog::create([
            'part_type' => $stock->part_type,
            'sn' => $sn,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Verif Admin',
            'action_type' => 'SUBMIT_RE_REGISTER_REQUEST',
            'actor_username' => $operator,
            'actor_role' => auth()->user()?->role ?: 'operator',
            'reason' => $notes ?: 'Pengajuan pendaftaran ulang part (Menunggu Verifikasi Admin)',
        ]);

        return $this->successResponse(null, "Permohonan pendaftaran ulang part {$sn} berhasil diajukan dan sedang menunggu verifikasi/ACC Admin.");
    }



}


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
                WHEN m.status = "Tersedia" THEN "Tersedia"
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
                DB::raw('COUNT(CASE WHEN m.status = "Tersedia" THEN 1 END) as available'),
                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) = "Supplied" THEN 1 END) as allocated'),
                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) = "Done" THEN 1 END) as used'),
                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) IN ("Good", "Done") THEN 1 END) as good_count'),
                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) = "Hold" THEN 1 END) as hold_count'),
                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) = "Scrap" THEN 1 END) as scrap_count'),
                DB::raw('COUNT(CASE WHEN m.status != "Tersedia" AND (a.is_declined = 1 OR a.verified_status = "DECLINED" OR m.status IN ("Declined", "Decline", "Pending Verif Admin")) THEN NULL WHEN m.status != "Tersedia" AND COALESCE(a.status, m.status) = "Rework" THEN 1 END) as rework_count'),
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

        $limit = ($limitParam && is_numeric($limitParam) && (int)$limitParam > 0) ? min((int)$limitParam, 200) : 60;
        $query->limit($limit);

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
        } elseif ($stock->is_replaced_from_assy == 1 || $stock->status === 'Part Diganti') {
            $lifecycle['stage_key'] = 'PART_DIGANTI';
            $lifecycle['stage_title'] = 'Part Rusak Saat Perakitan (Telah Diganti)';
            $lifecycle['badge_color'] = 'rose';
            $lifecycle['current_location'] = 'Karantina Material / Evaluasi Admin';
            $lifecycle['description'] = 'Part telah dilepas dari set rakitan asalnya dan digantikan oleh part baru.';
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
            $tray = $assembly->tray;
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
            $tray = $assembly->tray;
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
            $tray = $assembly->tray;
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
        } elseif ($assembly && strtoupper($assembly->assy_test_status ?? '') === 'HOLD') {
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

        $auditLogs = DB::table('part_audit_logs')
            ->where('sn', $sn)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse([
            'stock' => $stock,
            'tray' => $tray,
            'assembly' => $assembly,
            'shipping' => $shipping,
            'lifecycle' => $lifecycle,
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

        DB::table('master_stocks')
            ->where('sn', $sn)
            ->update([
                'status' => 'Tersedia',
                'allocated_tray_id' => null,
                'allocated_at' => null,
                'used_at' => null,
                'last_tray_id' => null,
                'is_replaced_from_assy' => 0,
                'last_reason' => $notes ?: 'Daftar ulang part ke status Tersedia',
                'last_operator' => $operator,
                'updated_at' => now(),
            ]);

        PartAuditLog::create([
            'part_type' => $stock->part_type,
            'sn' => $sn,
            'old_status' => $oldStatus,
            'new_status' => 'Tersedia',
            'action_type' => 'RE_REGISTER_PART',
            'actor_username' => $operator,
            'actor_role' => auth()->user()?->role ?: 'operator',
            'reason' => $notes ?: 'Daftar ulang part ke status Tersedia',
        ]);
            
        return $this->successResponse(null, "Part {$sn} berhasil diregister ulang dan kembali Tersedia tanpa ikatan set lama.");
    }

}

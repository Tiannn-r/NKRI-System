<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryOrder;
use App\Models\PackingList;
use App\Models\PackingListBox;
use App\Models\PackingListItem;
use App\Models\DoSetting;
use App\Models\Customer;
use App\Services\CloudinaryService;

class DeliveryOrderController extends Controller
{
    protected CloudinaryService $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * Dashboard Stats DO: Total Rakit, Siap Kirim, Terkirim, Chart Good/Hold/Scrap
     */
    public function dashboardStats()
    {
        // a. Total keseluruhan set yang sudah dirakit (mencakup seluruh hasil riwayat fisik perakitan: Good, Hold, Scrap)
        $totalAssembledSets = DB::table('assemblies')
            ->where(function ($w) {
                $w->whereNotNull('finished_at')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('status')->where('status', '!=', '');
                  });
            })
            ->count();
        $totalAssembledParts = $totalAssembledSets * 3;

        // b. Set yang sudah siap kirim (Good Receipts / lolos hingga gudang ACC)
        $shippedSNs = DB::table('packing_list_items')->pluck('serial_number')->toArray();
        $readyToShipQuery = DB::table('assemblies as a')
            ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
            ->where(function ($w) {
                $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
            });
        if (!empty($shippedSNs)) {
            $readyToShipQuery->whereNotIn('a.part15_sn', $shippedSNs);
        }
        $readyToShipSets = $readyToShipQuery->count();
        $readyToShipParts = $readyToShipSets * 3;

        // c. Sudah dikirim (sudah masuk packing list / surat jalan)
        $shippedSets = count($shippedSNs);
        $shippedParts = $shippedSets * 3;

        // d. Chart barang Good, Hold, Scrap berdasarkan status murni di history perakitan
        $goodCount = DB::table('assemblies')
            ->where(function ($w) {
                $w->where('original_assembly_status', 'Good')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('original_assembly_status')
                          ->where('status', 'Good');
                  });
            })->count();

        $holdCount = DB::table('assemblies')
            ->where(function ($w) {
                $w->where('original_assembly_status', 'Hold')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('original_assembly_status')
                          ->where('status', 'Hold');
                  });
            })->count();

        $scrapCount = DB::table('assemblies')
            ->where(function ($w) {
                $w->where('original_assembly_status', 'Scrap')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('original_assembly_status')
                          ->where('status', 'Scrap');
                  });
            })->count();

        // Summary dokumen
        $totalDO = DB::table('delivery_orders')->count();
        $totalPL = DB::table('standalone_packing_labels')->count();
        $totalCustomers = DB::table('customers')->count();

        return response()->json([
            'ok' => true,
            'data' => [
                'total_assembled_sets' => $totalAssembledSets,
                'total_assembled_parts' => $totalAssembledParts,
                'ready_to_ship_sets' => $readyToShipSets,
                'ready_to_ship_parts' => $readyToShipParts,
                'shipped_sets' => $shippedSets,
                'shipped_parts' => $shippedParts,
                'quality' => [
                    'good_sets' => $goodCount,
                    'good_parts' => $goodCount * 3,
                    'hold_sets' => $holdCount,
                    'hold_parts' => $holdCount * 3,
                    'scrap_sets' => $scrapCount,
                    'scrap_parts' => $scrapCount * 3,
                ],
                'summary' => [
                    'total_do' => $totalDO,
                    'total_pl' => $totalPL,
                    'total_customers' => $totalCustomers
                ]
            ]
        ]);
    }

    /**
     * Cek keunikan nomor dokumen (SJ atau PL) secara instan
     */
    public function checkUniqueDocNumber(Request $request)
    {
        $type = strtolower(trim($request->input('type', 'sj')));
        $number = trim($request->input('number', ''));

        if (empty($number)) {
            return response()->json(['ok' => true, 'is_unique' => true]);
        }

        if ($type === 'crate') {
            $inPl = DB::table('packing_lists')->where('crate_id', $number)->exists();
            $inSpl = DB::table('standalone_packing_labels')->where('crates_json', 'like', "%\"crate_id\":\"{$number}\"%")->exists();
            $exists = $inPl || $inSpl;
            return response()->json([
                'ok' => true,
                'type' => 'crate',
                'number' => $number,
                'is_unique' => !$exists,
                'message' => $exists ? "ID Peti {$number} sudah pernah dibuat atau terdaftar! Silakan sesuaikan nomor batch atau nomor pengiriman." : "ID Peti tersedia."
            ]);
        } elseif ($type === 'box') {
            $inBox = DB::table('packing_list_boxes')->where('box_id', $number)->exists();
            $inSpl = DB::table('standalone_packing_labels')->where('crates_json', 'like', "%\"box_id\":\"{$number}\"%")->exists();
            $exists = $inBox || $inSpl;
            return response()->json([
                'ok' => true,
                'type' => 'box',
                'number' => $number,
                'is_unique' => !$exists,
                'message' => $exists ? "ID Dus {$number} sudah pernah dibuat atau terdaftar! Silakan sesuaikan nomor batch atau nomor pengiriman." : "ID Dus tersedia."
            ]);
        } elseif ($type === 'pl') {
            $inPl = DB::table('packing_lists')->where('pl_number', $number)->exists();
            $inLbl = DB::table('standalone_packing_labels')->where('pl_number', $number)->exists();
            $inDo = DB::table('delivery_orders')->where('pl_number', $number)->exists();
            $exists = $inPl || $inLbl || $inDo;
            return response()->json([
                'ok' => true,
                'type' => 'pl',
                'number' => $number,
                'is_unique' => !$exists,
                'message' => $exists ? "Packing List dengan nomor {$number} sudah dibuat! Silakan cek terlebih dahulu." : "Nomor Packing List tersedia."
            ]);
        } else {
            $inDo = DB::table('delivery_orders')->where('do_number', $number)->exists();
            return response()->json([
                'ok' => true,
                'type' => 'sj',
                'number' => $number,
                'is_unique' => !$inDo,
                'message' => $inDo ? "Surat Jalan dengan nomor {$number} sudah dibuat! Silakan cek terlebih dahulu." : "Nomor Surat Jalan tersedia."
            ]);
        }
    }

    /**
     * Mengambil daftar Delivery Orders
     */
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
        $sn = trim($request->input('sn', ''));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $docFilter = $request->input('doc_number');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = DeliveryOrder::with('packingLists.boxes.items')
            ->orderBy('do_date', $sortOrder)
            ->orderBy('id', $sortOrder);

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('do_number', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('contract_number', 'like', "%{$q}%")
                  ->orWhere('program_name', 'like', "%{$q}%")
                  ->orWhere('pl_number', 'like', "%{$q}%")
                  ->orWhereHas('packingLists.boxes.items', function ($bi) use ($q) {
                      $bi->where('serial_number', 'like', "%{$q}%");
                  });
            });
        }

        if ($sn) {
            $query->whereHas('packingLists.boxes.items', function ($bi) use ($sn) {
                $bi->where('serial_number', 'like', "%{$sn}%");
            });
        }

        if ($docFilter) {
            $query->where('do_number', $docFilter);
        }

        if ($startDate) {
            $query->where('do_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('do_date', '<=', $endDate);
        }

        $limitParam = $request->input('limit');
        if ($limitParam === 'all' || $request->has('all') || !$request->has('page')) {
            $allData = $query->get();
            return response()->json([
                'ok' => true,
                'total' => $allData->count(),
                'data' => $allData,
                'current_page' => 1,
                'last_page' => 1,
            ]);
        }

        $items = $query->paginate(20);

        return response()->json([
            'ok' => true,
            'total' => $items->total(),
            'data' => $items->items(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
        ]);
    }

    /**
     * Melacak posisi Serial Number Part 15 ke Delivery Order, Peti, Dus, dan Slot
     */
    public function trackSerialNumber(Request $request)
    {
        $sn = trim($request->input('sn', ''));
        if (empty($sn)) {
            return response()->json([
                'ok' => false,
                'message' => 'Serial Number Part 15 wajib diisi.'
            ], 400);
        }

        // 1. Cari di tabel relasional Delivery Orders & Packing Lists
        $item = DB::table('packing_list_items as pli')
            ->leftJoin('packing_list_boxes as plb', 'plb.id', '=', 'pli.box_id')
            ->leftJoin('packing_lists as pl', 'pl.id', '=', 'plb.packing_list_id')
            ->join('delivery_orders as do', 'do.id', '=', 'pli.delivery_order_id')
            ->where('pli.serial_number', 'like', "%{$sn}%")
            ->select(
                'pli.serial_number',
                'pli.slot_number',
                'plb.box_number',
                'plb.box_id',
                'pl.crate_number',
                'pl.crate_id',
                'pl.pl_number',
                'do.id as do_id',
                'do.do_number',
                'do.do_date',
                'do.customer_name',
                'do.contract_number',
                'do.program_name',
                'do.status',
                'do.batch_number'
            )
            ->first();

        if ($item) {
            $deliverySeq = 1;
            if (preg_match('/^0*(\d+)/', $item->do_number, $m)) {
                $deliverySeq = (int)$m[1];
            }

            return response()->json([
                'ok' => true,
                'found' => true,
                'type' => 'delivery_order',
                'data' => [
                    'serial_number' => $item->serial_number,
                    'slot_number' => $item->slot_number,
                    'delivery_id' => $item->do_id,
                    'do_number' => $item->do_number,
                    'do_date' => $item->do_date,
                    'delivery_seq' => $deliverySeq,
                    'customer_name' => $item->customer_name,
                    'contract_number' => $item->contract_number,
                    'program_name' => $item->program_name,
                    'crate_number' => $item->crate_number,
                    'crate_id' => $item->crate_id,
                    'box_number' => $item->box_number,
                    'box_id' => $item->box_id,
                    'pl_number' => $item->pl_number,
                    'status' => $item->status,
                ]
            ]);
        }

        // 2. Jika belum ditemukan, periksa Standalone Packing Labels
        $labels = DB::table('standalone_packing_labels')->get();
        foreach ($labels as $lbl) {
            $crates = json_decode($lbl->crates_json, true) ?: [];
            foreach ($crates as $cIdx => $c) {
                foreach ($c['boxes'] ?? [] as $bIdx => $b) {
                    foreach ($b['items'] ?? [] as $it) {
                        $itSn = is_array($it) ? ($it['serial_number'] ?? '') : (string)$it;
                        if (!empty($itSn) && stripos($itSn, $sn) !== false) {
                            return response()->json([
                                'ok' => true,
                                'found' => true,
                                'type' => 'standalone_label',
                                'data' => [
                                    'serial_number' => $itSn,
                                    'slot_number' => is_array($it) ? ($it['slot_number'] ?? ($bIdx + 1)) : 1,
                                    'label_batch_id' => $lbl->label_batch_id,
                                    'pl_number' => $lbl->pl_number,
                                    'delivery_seq' => 1,
                                    'crate_number' => $c['crate_number'] ?? ($cIdx + 1),
                                    'crate_id' => $c['crate_id'] ?? '',
                                    'box_number' => $b['box_number'] ?? ($bIdx + 1),
                                    'box_id' => $b['box_id'] ?? '',
                                    'customer_name' => 'Label Standalone (Gudang)',
                                    'status' => 'Tersimpan di Label Standalone'
                                ]
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'found' => false,
            'message' => "Serial Number '{$sn}' tidak ditemukan pada riwayat pengiriman manapun."
        ]);
    }

    /**
     * Serial Number yang sudah Lulus TF 2 (QC) dan Siap untuk Delivery Order
     */
    public function availableSerialNumbers()
    {
        // Ambil seluruh unit yang sudah digunakan di Surat Jalan (DO)
        $usedSns = PackingListItem::pluck('serial_number')->map(fn($s) => strtoupper(trim($s)))->toArray();

        // Ambil juga unit yang sudah dialokasikan ke Standalone Packing Labels
        $standaloneLabels = DB::table('standalone_packing_labels')->pluck('crates_json');
        foreach ($standaloneLabels as $cj) {
            $crates = json_decode($cj, true) ?: [];
            foreach ($crates as $c) {
                foreach ($c['boxes'] ?? [] as $b) {
                    foreach ($b['items'] ?? [] as $it) {
                        $sn = is_array($it) ? ($it['serial_number'] ?? '') : (string)$it;
                        $sn = strtoupper(trim($sn));
                        if (!empty($sn) && !in_array($sn, $usedSns)) {
                            $usedSns[] = $sn;
                        }
                    }
                }
            }
        }

        // Ambil unit yang telah melewati SEMUA proses hingga Penerimaan Fisik Gudang (ACC)
        // Unit valid mencakup yang langsung Good maupun yang setelah Hold / Dirakit Ulang telah lolos verifikasi Admin hingga ACC Gudang
        $available = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->join('master_stocks as m15', function ($join) {
                $join->on('a.part15_sn', '=', 'm15.sn')
                     ->where('m15.part_type', '=', '15');
            })
            ->where(function ($w) {
                $w->where('a.status', 'Good')
                  ->orWhere('a.status', 'Dirakit Ulang')
                  ->orWhere('a.status', 'Selesai')
                  ->orWhereIn('a.verified_status', ['APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'APPROVED_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_PROCEED_TF1', 'APPROVED_PROCEED_TF2', 'ACC']);
            })
            ->where(function ($w) {
                $w->where('a.assy_test_status', 'Good')
                  ->orWhere('a.assy_test_status', 'Pass')
                  ->orWhere('a.assy_test_status', 'Lolos')
                  ->orWhereIn('a.verified_status', ['APPROVED_QC', 'APPROVED_PROCEED_TF2', 'APPROVED_PROCEED_QC', 'ACC', 'APPROVED']);
            })
            ->where(function ($w) {
                $w->whereIn(DB::raw("UPPER(COALESCE(a.qc_status, ''))"), ['GOOD', 'GOOD_QC', 'PASS', 'ACC'])
                  ->orWhereIn('a.verified_status', ['APPROVED_QC', 'ACC', 'APPROVED']);
            })
            ->where(function ($w) {
                $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
            })
            ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
            ->whereNotIn('m15.status', ['Scrap', 'Part Diganti', 'Declined', 'Decline'])
            ->whereNotIn('a.part15_sn', $usedSns)
            ->select([
                'a.part15_sn as serial_number',
                'a.part1_sn',
                'a.part12_sn',
                't.tray_code',
                'a.set_number',
                'a.qc_tested_at',
            ])
            ->distinct()
            ->orderBy('a.part15_sn', 'asc')
            ->get();

        return $this->successResponse($available);
    }

    /**
     * Mengambil nomor DO berikutnya, nomor batch, dan konfigurasi penomoran (next-meta)
     */
    public function nextMeta(Request $request)
    {
        $dateStr = $request->input('date', now()->format('Y-m-d'));
        $date = \Carbon\Carbon::parse($dateStr);
        $settings = DoSetting::first();

        $prefix = $settings?->do_prefix ?: '/SJ/PPIC/';
        $plPrefix = $settings?->pl_prefix ?: '/PL/PPIC/';
        $cratePrefix = $settings?->crate_prefix ?: '2609-';

        $month = $date->format('m');
        $year = $date->format('Y');

        // Nomor batch aktif dikendalikan terpusat dari Admin CMS
        $currentBatch = max(1, (int)($settings?->current_batch_number ?: 1));

        // Auto count urutan pengiriman terpisah per batch:
        // SJ Count:
        $sjCount = DeliveryOrder::where('batch_number', $currentBatch)->count();
        $nextSjSeq = $sjCount + 1;

        // PL Count:
        $plCount = DB::table('standalone_packing_labels')->where('batch_number', $currentBatch)->count();
        $nextPlSeq = $plCount + 1;

        // Cari nomor urut dokumen terakhir pada bulan & tahun ini
        $lastDo = DeliveryOrder::whereYear('do_date', $year)
            ->whereMonth('do_date', $month)
            ->latest('id')
            ->first();

        $seq = 1;
        if ($lastDo && preg_match('/^(\d+)/', $lastDo->do_number, $m)) {
            $seq = (int)$m[1] + 1;
        }

        $seqStr = str_pad($seq, 3, '0', STR_PAD_LEFT);
        $nextDoNumber = "{$seqStr}{$prefix}{$month}/{$year}";
        $nextPlNumber = "{$seqStr}{$plPrefix}{$month}/{$year}";

        $batch2Digit = str_pad($currentBatch, 2, '0', STR_PAD_LEFT);
        $pp2DigitSj = str_pad($nextSjSeq, 2, '0', STR_PAD_LEFT);
        $pp2DigitPl = str_pad($nextPlSeq, 2, '0', STR_PAD_LEFT);
        $yymm = $date->format('y') . $month;

        // Format Standar: YYMM-BB-PPDD (Peti) & YYMM-BB-PPDD-SS (Dus)
        $cratePrefix = "{$yymm}-{$batch2Digit}-{$pp2DigitSj}";
        $plCratePrefix = "{$yymm}-{$batch2Digit}-{$pp2DigitPl}";
        $nextBatchNumber = "{$yymm}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

        return $this->successResponse([
            'do_number' => $nextDoNumber,
            'next_number' => $nextDoNumber,
            'next_pl_number' => $nextPlNumber,
            'next_batch' => $nextBatchNumber,
            'current_batch' => $currentBatch,
            'batch_number' => $currentBatch,
            'next_sj_seq' => $nextSjSeq,
            'next_pl_seq' => $nextPlSeq,
            'delivery_seq' => $nextSjSeq,
            'sequence' => $seq,
            'do_date_iso' => $date->format('Y-m-d'),
            'crate_prefix' => $cratePrefix,
            'pl_crate_prefix' => $plCratePrefix,
            'crate_sequence' => $nextSjSeq,
            'box_sequence' => 1,
            'customer_name' => $settings?->default_customer_name ?: 'KEMHAN RI',
            'customer_address' => $settings?->default_customer_address ?: '',
            'customer_phone' => $settings?->default_customer_phone ?: '',
            'customer_fax' => $settings?->default_customer_fax ?: '',
            'contract_number' => $settings?->default_contract_number ?: 'TRAK/2509/APBN/XI/2025/KEMHAN',
            'program_name' => $settings?->default_program_name ?: 'PENGADAAN ALPALHANKAM MATSUSSUS',
            'product_name' => $settings?->default_product_name ?: 'Nanggala P9',
            'created_by_name' => $settings?->default_issuer_name ?: 'Rezka Rachdana',
            'created_by_title' => $settings?->default_issuer_title ?: 'Kepala Departemen',
            'created_by_department' => $settings?->default_issuer_department ?: 'Logistik & PPIC',
            'sample_crate_id' => "{$cratePrefix}01",
            'sample_box_id' => "{$cratePrefix}01-01",
            'crate_capacity' => $settings?->crate_capacity ?: 20,
            'box_capacity' => $settings?->box_capacity ?: 10,
            'settings' => $settings,
        ]);
    }

    /**
     * Menyimpan Delivery Order baru beserta Packing List secara atomik
     */
    public function store(Request $request)
    {
        $doNum = trim($request->input('do_number', ''));
        if (empty($doNum)) {
            return response()->json(['ok' => false, 'error' => 'Nomor Surat Jalan (DO) wajib diisi!'], 400);
        }

        // 1. Cek Keunikan ID Surat Jalan (ID SJ)
        if (DB::table('delivery_orders')->where('do_number', $doNum)->exists()) {
            return response()->json([
                'ok' => false,
                'error' => "Surat Jalan dengan nomor {$doNum} sudah dibuat! Silakan cek terlebih dahulu."
            ], 422);
        }

        // 2. Cek Keunikan ID Packing List (ID PL)
        $plNum = trim($request->input('pl_number', ''));
        if (!empty($plNum)) {
            $inPl = DB::table('packing_lists')->where('pl_number', $plNum)->exists();
            $inLbl = DB::table('standalone_packing_labels')->where('pl_number', $plNum)->exists();
            $inDo = DB::table('delivery_orders')->where('pl_number', $plNum)->exists();
            if ($inPl || $inLbl || $inDo) {
                return response()->json([
                    'ok' => false,
                    'error' => "Packing List dengan nomor {$plNum} sudah dibuat! Silakan cek terlebih dahulu."
                ], 422);
            }
        }

        $request->validate([
            'do_number' => 'required|string',
            'do_date' => 'required|date',
            'customer_name' => 'required|string',
            'crates' => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $crates = $request->input('crates', []);
            $totalCrates = count($crates);
            $totalBoxes = 0;
            $totalUnits = 0;

            $doDateStr = $request->input('do_date') ?: now()->toDateString();
            $targetDt = \Carbon\Carbon::parse($doDateStr);
            $sjMode = strtolower(trim((string)$request->input('sj_mode', '')));
            $isBackdate = $request->boolean('is_backdate') || ($sjMode === 'backdate') || ($targetDt->toDateString() < now()->toDateString());
            if (!$sjMode) {
                $sjMode = $isBackdate ? 'backdate' : 'realtime';
            }

            $consumedPlIdsToMark = [];

            // Periksa consumed PL dari input eksplisit
            $explicitPlIds = (array) $request->input('consumed_pl_ids', []);
            if ($request->filled('consumed_standalone_id')) {
                $explicitPlIds[] = $request->input('consumed_standalone_id');
            }
            foreach ($crates as $c) {
                if (!empty($c['consumed_pl_id'])) $explicitPlIds[] = $c['consumed_pl_id'];
                foreach ($c['boxes'] ?? [] as $b) {
                    if (!empty($b['consumed_pl_id'])) $explicitPlIds[] = $b['consumed_pl_id'];
                }
            }
            $explicitPlIds = array_unique(array_filter($explicitPlIds));

            if (!empty($explicitPlIds)) {
                $checkPls = DB::table('standalone_packing_labels')->whereIn('id', $explicitPlIds)->get();
                foreach ($checkPls as $cpl) {
                    // Aturan 1: Hanya bisa di-consume 1 kali
                    if ($cpl->is_consumed || $cpl->delivery_order_id) {
                        return response()->json([
                            'ok' => false,
                            'error' => "Dokumen Packing List {$cpl->pl_number} sudah pernah digunakan (di-consume) pada Surat Jalan lain dan tidak dapat digunakan lagi!"
                        ], 422);
                    }
                    // Aturan 2: Mode harus sama (backdate -> backdate, realtime -> realtime)
                    $cplMode = $cpl->pl_mode ?: ($cpl->is_backdate ? 'backdate' : 'realtime');
                    if ($cplMode !== $sjMode) {
                        return response()->json([
                            'ok' => false,
                            'error' => "Ketidakcocokan Jenis Dokumen: Packing List {$cpl->pl_number} dibuat dengan mode '{$cplMode}', sehingga tidak dapat dikonsumsi oleh Surat Jalan yang dibuat dengan mode '{$sjMode}'. Syarat consume adalah jenis mode harus sama."
                        ], 422);
                    }
                    $consumedPlIdsToMark[] = $cpl->id;
                }
            }

            foreach ($crates as $c) {
                foreach ($c['boxes'] ?? [] as $b) {
                    $totalBoxes++;
                    foreach ($b['items'] ?? [] as $it) {
                        $sn = trim(is_array($it) ? ($it['serial_number'] ?? '') : (string)$it);
                        if (!empty($sn)) {
                            $totalUnits++;
                        }
                    }
                }
            }

            // VALIDASI KEPATUHAN ALUR & ANTI-DUPLIKASI SURAT JALAN
            $seenInPayload = [];
            foreach ($crates as $c) {
                foreach ($c['boxes'] ?? [] as $b) {
                    foreach ($b['items'] ?? [] as $it) {
                        $sn = trim($it['serial_number'] ?? '');
                        if (!empty($sn)) {
                            // 1. Anti-duplikasi di payload sendiri
                            if (isset($seenInPayload[$sn])) {
                                return response()->json([
                                    'ok' => false,
                                    'error' => "Nomor seri {$sn} terduplikasi di dalam dokumen pengiriman ini!"
                                ], 400);
                            }
                            $seenInPayload[$sn] = true;

                            // 2. Anti-duplikasi dengan Surat Jalan yang sudah terbit sebelumnya
                            $alreadyInDo = PackingListItem::where('serial_number', $sn)->first();
                            if ($alreadyInDo) {
                                return response()->json([
                                    'ok' => false,
                                    'error' => "Nomor seri {$sn} sudah pernah terdaftar pada Surat Jalan ID #{$alreadyInDo->delivery_order_id}!"
                                ], 400);
                            }

                            // Anti-duplikasi dengan Standalone Packing Label yang tersimpan (kecuali jika dikonsumsi ke DO ini)
                            $existingLabels = DB::table('standalone_packing_labels')->get(['id', 'pl_number', 'label_batch_id', 'crates_json', 'is_consumed', 'delivery_order_id', 'pl_mode', 'is_backdate']);
                            foreach ($existingLabels as $lblRow) {
                                $plNum = $lblRow->pl_number;
                                $cratesJsonArr = json_decode($lblRow->crates_json, true) ?: [];
                                foreach ($cratesJsonArr as $cjCrate) {
                                    foreach ($cjCrate['boxes'] ?? [] as $cjBox) {
                                        foreach ($cjBox['items'] ?? [] as $cjItem) {
                                            $exSn = strtoupper(trim(is_array($cjItem) ? ($cjItem['serial_number'] ?? '') : (string)$cjItem));
                                            if ($exSn === strtoupper($sn)) {
                                                // Jika part ini dikonsumsi secara sah dari Peti/Dus packing label tersebut, perbolehkan
                                                $consumedPL = $c['consumed_from_pl'] ?? ($b['consumed_from_pl'] ?? ($request->input('consumed_from_pl') ?? null));
                                                $consumedStandaloneId = $request->input('consumed_standalone_id');
                                                $consumedCrateId = $c['consumed_source_crate_id'] ?? ($c['crate_id'] ?? null);
                                                $consumedBoxId = $b['consumed_source_box_id'] ?? ($b['box_id'] ?? null);
                                                $isLegitConsume = ($consumedPL && ($consumedPL === $plNum || $consumedPL === $lblRow->label_batch_id)) ||
                                                                 ($consumedStandaloneId && $lblRow->id == $consumedStandaloneId) ||
                                                                 ($consumedCrateId && isset($cjCrate['crate_id']) && $consumedCrateId === $cjCrate['crate_id']) ||
                                                                 ($consumedBoxId && isset($cjBox['box_id']) && $consumedBoxId === $cjBox['box_id']) ||
                                                                 ($consumedBoxId && isset($cjCrate['box_id']) && $consumedBoxId === $cjCrate['box_id']) ||
                                                                 ($consumedBoxId && $consumedBoxId === $lblRow->label_batch_id) ||
                                                                 ($consumedBoxId && $consumedBoxId === $plNum);
                                                if (!$isLegitConsume) {
                                                    return response()->json([
                                                        'ok' => false,
                                                        'error' => "Nomor seri {$sn} sudah pernah terdaftar pada Dokumen Packing List {$plNum}!"
                                                    ], 400);
                                                }

                                                // Validasi Aturan 1: PL hanya bisa di-consume 1 kali
                                                if ($lblRow->is_consumed || $lblRow->delivery_order_id) {
                                                    return response()->json([
                                                        'ok' => false,
                                                        'error' => "Dokumen Packing List {$plNum} sudah pernah digunakan (di-consume) pada Surat Jalan lain dan tidak dapat digunakan lagi!"
                                                    ], 422);
                                                }

                                                // Validasi Aturan 2: Jenis mode harus sama persis (backdate -> backdate, realtime -> realtime)
                                                $lblMode = $lblRow->pl_mode ?: ($lblRow->is_backdate ? 'backdate' : 'realtime');
                                                if ($lblMode !== $sjMode) {
                                                    return response()->json([
                                                        'ok' => false,
                                                        'error' => "Ketidakcocokan Jenis Dokumen: Packing List {$plNum} dibuat dengan mode '{$lblMode}', sedangkan Surat Jalan dibuat dengan mode '{$sjMode}'. Syarat consume adalah jenis mode harus sama."
                                                    ], 422);
                                                }

                                                $consumedPlIdsToMark[] = $lblRow->id;
                                            }
                                        }
                                    }
                                }
                            }

                            // 3. Validasi kepatuhan alur: Wajib telah melewati seluruh alur sampai Penerimaan Fisik Gudang (ACC)
                            // Baik yang langsung Good maupun yang setelah Hold / Dirakit Ulang telah diverifikasi Admin hingga ACC Gudang
                            $validUnit = DB::table('assemblies as a')
                                ->join('master_stocks as m15', function ($j) {
                                    $j->on('a.part15_sn', '=', 'm15.sn')->where('m15.part_type', '15');
                                })
                                ->where('a.part15_sn', $sn)
                                ->where(function ($w) {
                                    $w->where('a.status', 'Good')
                                      ->orWhere('a.status', 'Dirakit Ulang')
                                      ->orWhere('a.status', 'Selesai')
                                      ->orWhereIn('a.verified_status', ['APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'APPROVED_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_PROCEED_TF1', 'APPROVED_PROCEED_TF2', 'ACC']);
                                })
                                ->where(function ($w) {
                                    $w->where('a.assy_test_status', 'Good')
                                      ->orWhere('a.assy_test_status', 'Pass')
                                      ->orWhere('a.assy_test_status', 'Lolos')
                                      ->orWhereIn('a.verified_status', ['APPROVED_QC', 'APPROVED_PROCEED_TF2', 'APPROVED_PROCEED_QC', 'ACC', 'APPROVED']);
                                })
                                ->where(function ($w) {
                                    $w->whereIn(DB::raw("UPPER(COALESCE(a.qc_status, ''))"), ['GOOD', 'GOOD_QC', 'PASS', 'ACC'])
                                      ->orWhereIn('a.verified_status', ['APPROVED_QC', 'ACC', 'APPROVED']);
                                })
                                ->where(function ($w) {
                                    $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                                })
                                ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
                                ->whereNotIn('m15.status', ['Scrap', 'Part Diganti', 'Declined', 'Decline'])
                                ->exists();

                            if (!$validUnit) {
                                return response()->json([
                                    'ok' => false,
                                    'error' => "Nomor seri {$sn} tidak valid untuk Surat Jalan! Unit harus telah menyelesaikan seluruh alur (Rakit Good -> TF 1 Good -> QC Good -> Penerimaan Fisik Gudang ACCEPT) dan tidak sedang berstatus Hold/Scrap/Tersedia."
                                ], 400);
                            }
                        }
                    }
                }
            }

            $settings = DoSetting::first();

            $do = DeliveryOrder::create([
                'do_number' => trim($request->do_number),
                'pl_number' => $request->input('pl_number') ?: str_replace('/SJ/', '/PL/', $request->do_number),
                'do_date' => $targetDt->toDateString(),
                'is_backdate' => $isBackdate ? 1 : 0,
                'sj_mode' => $sjMode,
                'contract_number' => trim((string)$request->input('contract_number')) ?: ($settings?->default_contract_number ?: 'TRAK/2509/APBN/XI/2025/KEMHAN'),
                'program_name' => trim((string)$request->input('program_name')) ?: ($settings?->default_program_name ?: 'PENGADAAN ALPALHANKAM MATSUSSUS'),
                'product_name' => trim((string)$request->input('product_name')) ?: ($settings?->default_product_name ?: 'Nanggala P9'),
                'batch_number' => max(1, (int)($request->input('batch_number') ?: ($settings?->current_batch_number ?: 1))),
                'customer_name' => trim($request->customer_name),
                'customer_address' => $request->input('customer_address') ?: ($settings?->default_customer_address ?: ''),
                'customer_phone' => preg_replace('/\p{L}/u', '', (string)$request->input('customer_phone', '')) ?: ($settings?->default_customer_phone ?: ''),
                'customer_fax' => preg_replace('/\p{L}/u', '', (string)$request->input('customer_fax', '')) ?: ($settings?->default_customer_fax ?: ''),
                'package_type' => $request->input('package_type', 'peti'),
                'total_crates' => $totalCrates,
                'total_boxes' => $totalBoxes,
                'total_units' => $totalUnits,
                'status' => 'ISSUED',
                'created_by' => auth()->user()?->username ?: 'PPIC',
                'created_by_name' => auth()->user()?->full_name ?: ($settings?->default_issuer_name ?: 'Rezka Rachdana'),
                'created_by_title' => $request->input('created_by_title') ?: ($settings?->default_issuer_title ?: 'Staf PPIC'),
                'created_by_department' => $request->input('created_by_department') ?: ($settings?->default_issuer_department ?: 'Logistik & PPIC'),
                'notes' => $request->input('notes'),
                'created_at' => $isBackdate ? $targetDt->toDateTimeString() : now(),
                'updated_at' => now(),
            ]);

            foreach ($crates as $cIndex => $c) {
                $pl = PackingList::create([
                    'delivery_order_id' => $do->id,
                    'crate_number' => $c['crate_number'] ?? ($cIndex + 1),
                    'crate_id' => $c['crate_id'],
                    'pl_number' => $c['pl_number'] ?? $do->pl_number,
                    'total_quantity' => $c['total_quantity'] ?? 20,
                ]);

                foreach ($c['boxes'] ?? [] as $bIndex => $b) {
                    $box = PackingListBox::create([
                        'packing_list_id' => $pl->id,
                        'box_number' => $b['box_number'] ?? ($bIndex + 1),
                        'box_id' => $b['box_id'],
                        'total_quantity' => count($b['items'] ?? []),
                    ]);

                    foreach ($b['items'] ?? [] as $slotIndex => $it) {
                        $sn = trim($it['serial_number'] ?? '');
                        if (!empty($sn)) {
                            PackingListItem::create([
                                'delivery_order_id' => $do->id,
                                'box_id' => $box->id,
                                'slot_number' => $it['slot_number'] ?? ($slotIndex + 1),
                                'serial_number' => $sn,
                            ]);
                        }
                    }
                }
            }

            // Tandai seluruh Dokumen Packing List (PL) yang dikonsumsi menjadi is_consumed = 1
            if (!empty($consumedPlIdsToMark)) {
                DB::table('standalone_packing_labels')
                    ->whereIn('id', array_unique($consumedPlIdsToMark))
                    ->update([
                        'is_consumed' => 1,
                        'delivery_order_id' => $do->id,
                        'consumed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $this->successResponse($do, "Delivery Order {$do->do_number} berhasil diterbitkan.", 201);
        });
    }

    /**
     * Detail DO lengkap beserta relasi Crates, Boxes, dan Items
     */
    public function show($id)
    {
        $do = DeliveryOrder::with(['packingLists.boxes.items'])->findOrFail($id);
        $settings = DoSetting::first();

        $data = $do->toArray();
        $data['total_quantity'] = $do->total_quantity;
        $data['total_units'] = $do->total_quantity;
        $data['total_boxes'] = $do->total_boxes;
        $data['total_crates'] = $do->total_crates;
        $data['contract_number'] = $do->contract_number ?: ($settings?->default_contract_number ?: 'TRAK/2509/APBN/XI/2025/KEMHAN');
        $data['program_name'] = $do->program_name ?: ($settings?->default_program_name ?: 'PENGADAAN ALPALHANKAM MATSUSSUS');
        $data['product_name'] = $do->product_name ?: ($settings?->default_product_name ?: 'Nanggala P9');
        $data['customer_name'] = $do->customer_name ?: ($settings?->default_customer_name ?: 'KEMHAN RI');
        $data['customer_address'] = $do->customer_address ?: ($settings?->default_customer_address ?: "BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 - 7\nJAKARTA, 10110");
        $data['created_by_name'] = $do->created_by_name ?: ($settings?->default_issuer_name ?: 'Rezka Rachdana');
        $data['created_by_title'] = $do->created_by_title ?: ($settings?->default_issuer_title ?: 'Staf PPIC');
        $data['crates'] = $do->packingLists->map(function ($pl) {
            return [
                'id' => $pl->id,
                'crate_number' => $pl->crate_number,
                'crate_id' => $pl->crate_id,
                'pl_number' => $pl->pl_number,
                'total_quantity' => $pl->total_quantity,
                'boxes' => $pl->boxes->map(function ($box) {
                    return [
                        'id' => $box->id,
                        'box_number' => $box->box_number,
                        'box_id' => $box->box_id,
                        'total_quantity' => $box->total_quantity,
                        'items' => $box->items->map(function ($it) {
                            return [
                                'slot_number' => $it->slot_number,
                                'serial_number' => $it->serial_number,
                            ];
                        }),
                    ];
                }),
            ];
        });

        $data['settings'] = $settings;
        $data['settings']['cloudinary'] = $this->cloudinary->getPublicInfo();

        return $this->successResponse($data);
    }

    /**
     * Upload Dokumen DO / PL PDF ke Cloudinary
     */
    public function uploadCloudPdf(Request $request)
    {
        $request->validate([
            'do_id' => 'required|integer',
            'pdf_base64' => 'required|string',
        ]);

        $do = DeliveryOrder::findOrFail($request->do_id);
        $publicId = 'DO_' . str_replace(['/', '\\'], '_', $do->do_number);

        $result = $this->cloudinary->uploadPdf($request->pdf_base64, $publicId);

        if ($result && !empty($result['secure_url'])) {
            $do->update([
                'cloud_pdf_url' => $result['secure_url'],
                'cloud_public_id' => $result['public_id'],
                'cloud_uploaded_at' => now(),
            ]);

            return $this->successResponse([
                'cloud_pdf_url' => $do->cloud_pdf_url,
                'cloud_uploaded_at' => $do->cloud_uploaded_at,
            ], 'Dokumen PDF berhasil diunggah ke Cloudinary.');
        }

        return $this->errorResponse('Gagal mengunggah dokumen ke Cloudinary.');
    }

    /**
     * Ekspor Data Delivery Order & Komponen Rakitannya (Clean Route Pengganti app.py:L4345/L4494)
     */
    public function exportData(Request $request)
    {
        $doId = $request->input('do_id') ?: $request->input('id');
        $docNumber = $request->input('doc_number');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $q = trim($request->input('q', ''));

        // Subquery untuk mengambil ID perakitan sah terakhir (Good / ACC Gudang) untuk setiap Part 15
        // Mencegah duplikasi baris akibat riwayat baki lama yang pernah dirakit ulang (PU)
        $validAssemblySub = DB::table('assemblies as a')
            ->select('a.part15_sn', DB::raw('MAX(a.id) as max_assembly_id'))
            ->where(function ($w) {
                $w->where('a.status', 'Good')
                  ->orWhereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
                  ->orWhereIn(DB::raw("UPPER(COALESCE(a.qc_status, ''))"), ['GOOD', 'GOOD_QC', 'PASS']);
            })
            ->where(function ($w) {
                $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
            })
            ->groupBy('a.part15_sn');

        $query = DB::table('delivery_orders as d')
            ->join('packing_list_items as pli', 'd.id', '=', 'pli.delivery_order_id')
            ->leftJoinSub($validAssemblySub, 'va', function ($join) {
                $join->on('pli.serial_number', '=', 'va.part15_sn');
            })
            ->leftJoin('assemblies as a', 'va.max_assembly_id', '=', 'a.id')
            ->select([
                'd.id as do_id',
                'd.do_number',
                'd.do_date',
                'd.created_at',
                DB::raw('COALESCE(d.created_by, "Administrator") as issued_by'),
                DB::raw('COALESCE(d.status, "ISSUED") as delivery_status'),
                DB::raw('COALESCE(d.customer_name, "-") as customer_name'),
                DB::raw('COALESCE(d.customer_address, "-") as customer_address'),
                'pli.serial_number',
                DB::raw('COALESCE(a.part12_sn, "-") as part12_sn'),
                DB::raw('COALESCE(a.part1_sn, "-") as part1_sn'),
            ]);

        if ($doId) {
            $query->where('d.id', $doId);
        }

        if ($docNumber) {
            $query->where('d.do_number', 'like', "%{$docNumber}%");
        }

        if ($startDate) {
            $query->where('d.do_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('d.do_date', '<=', $endDate);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('d.do_number', 'like', "%{$q}%")
                  ->orWhere('pli.serial_number', 'like', "%{$q}%")
                  ->orWhere('d.customer_name', 'like', "%{$q}%");
            });
        }

        $rows = $query->orderBy('d.id', 'desc')
            ->orderBy('pli.serial_number', 'asc')
            ->get();

        return $this->successResponse($rows);
    }

    /**
     * Hapus Delivery Order
     */
    public function destroy(Request $request)
    {
        $doId = $request->input('id');
        $do = DeliveryOrder::findOrFail($doId);

        if ($do->cloud_public_id) {
            $this->cloudinary->deleteFile($do->cloud_public_id);
        }

        // Lepaskan seluruh Standalone Packing Labels yang pernah dikonsumsi oleh DO ini
        DB::table('standalone_packing_labels')
            ->where('delivery_order_id', $doId)
            ->update([
                'is_consumed' => 0,
                'delivery_order_id' => null,
                'consumed_at' => null,
                'updated_at' => now(),
            ]);

        $do->delete(); // Cascade deletes packing_lists, boxes, items
        return $this->successResponse(null, 'Delivery Order berhasil dihapus.');
    }

    /**
     * Detail DO (alias untuk show)
     */
    public function detail(Request $request)
    {
        $id = $request->input('id') ?: $request->input('do_id');
        return $this->show($id);
    }

    /**
     * Hapus PDF Cloudinary dari Delivery Order
     */
    public function deleteCloudPdf(Request $request)
    {
        $doId = $request->input('do_id') ?: $request->input('id');
        $do = DeliveryOrder::find($doId);
        if ($do && $do->cloud_public_id) {
            $this->cloudinary->deleteFile($do->cloud_public_id);
            $do->update([
                'cloud_pdf_url' => null,
                'cloud_public_id' => null,
                'cloud_uploaded_at' => null,
            ]);
        }
        return $this->successResponse(null, 'PDF Cloud berhasil dihapus.');
    }

    /**
     * Informasi Status Konfigurasi Cloudinary
     */
    public function cloudinarySettings()
    {
        return $this->successResponse($this->cloudinary->getPublicInfo());
    }

    /**
     * Mengambil daftar customer
     */
    public function customers(Request $request)
    {
        $q = trim($request->input('q', ''));
        $query = Customer::orderBy('name', 'asc');
        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('address', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }
        return $this->successResponse($query->get());
    }

    /**
     * Tambah Customer Baru (atau update jika nama sudah ada)
     */
    public function createCustomer(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        $name = trim($request->input('name'));
        $phone = preg_replace('/\p{L}/u', '', (string)$request->input('phone', ''));
        $fax = preg_replace('/\p{L}/u', '', (string)$request->input('fax', ''));

        $existing = Customer::where('name', $name)->first();
        if ($existing) {
            $existing->update([
                'address' => trim($request->input('address', '')) ?: $existing->address,
                'phone' => trim($phone) ?: $existing->phone,
                'fax' => trim($fax) ?: $existing->fax,
            ]);
            return response()->json([
                'ok' => true,
                'customer' => $existing,
                'data' => $existing,
                'message' => 'Customer berhasil diperbarui.'
            ]);
        }

        $cust = Customer::create([
            'name' => $name,
            'address' => trim($request->input('address', '')),
            'phone' => trim($phone),
            'fax' => trim($fax),
        ]);

        return response()->json([
            'ok' => true,
            'customer' => $cust,
            'data' => $cust,
            'message' => 'Customer berhasil ditambahkan.'
        ]);
    }

    /**
     * Perbarui Customer
     */
    public function updateCustomer(Request $request)
    {
        $id = $request->input('id');
        $cust = Customer::findOrFail($id);
        $phoneInput = $request->has('phone') ? preg_replace('/\p{L}/u', '', (string)$request->input('phone', '')) : $cust->phone;
        $faxInput = $request->has('fax') ? preg_replace('/\p{L}/u', '', (string)$request->input('fax', '')) : $cust->fax;
        $cust->update([
            'name' => trim($request->input('name', $cust->name)),
            'address' => trim($request->input('address', $cust->address)),
            'phone' => trim($phoneInput),
            'fax' => trim($faxInput),
        ]);
        return response()->json([
            'ok' => true,
            'customer' => $cust,
            'data' => $cust,
            'message' => 'Customer berhasil diperbarui.'
        ]);
    }

    /**
     * Hapus Customer
     */
    public function deleteCustomer(Request $request, $id = null)
    {
        $targetId = $id ?: $request->input('id');
        Customer::where('id', $targetId)->delete();
        return response()->json([
            'ok' => true,
            'message' => 'Customer berhasil dihapus.'
        ]);
    }

    /**
     * Mengambil daftar Standalone Packing Labels
     */
    public function getPackingLabels()
    {
        $rows = DB::table('standalone_packing_labels')
            ->orderBy('label_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $consumedCrates = DB::table('packing_lists')
            ->join('delivery_orders', 'packing_lists.delivery_order_id', '=', 'delivery_orders.id')
            ->select('packing_lists.crate_id', 'delivery_orders.do_number')
            ->get()
            ->keyBy('crate_id');

        $consumedBoxes = DB::table('packing_list_boxes')
            ->join('packing_lists', 'packing_list_boxes.packing_list_id', '=', 'packing_lists.id')
            ->join('delivery_orders', 'packing_lists.delivery_order_id', '=', 'delivery_orders.id')
            ->select('packing_list_boxes.box_id', 'delivery_orders.do_number')
            ->get()
            ->keyBy('box_id');

        $data = $rows->map(function ($r) use ($consumedCrates, $consumedBoxes) {
            $rArray = (array) $r;
            $crates = json_decode($r->crates_json ?? '[]', true) ?: [];
            $rArray['crates'] = $crates;

            $packagingIds = [];
            $isConsumed = (bool) ($r->is_consumed ?? false);
            $consumedByDo = null;

            if (!empty($r->delivery_order_id)) {
                $doNum = DB::table('delivery_orders')->where('id', $r->delivery_order_id)->value('do_number');
                if ($doNum) {
                    $isConsumed = true;
                    $consumedByDo = $doNum;
                }
            }

            if ($r->package_type === 'crate') {
                foreach ($crates as $c) {
                    if (!empty($c['crate_id'])) {
                        $cId = trim($c['crate_id']);
                        $packagingIds[] = $cId;
                        if (!$isConsumed && isset($consumedCrates[$cId])) {
                            $isConsumed = true;
                            $consumedByDo = $consumedCrates[$cId]->do_number;
                        }
                    }
                }
            } else {
                foreach ($crates as $c) {
                    $bId = $c['box_id'] ?? ($c['boxes'][0]['box_id'] ?? null);
                    if (!empty($bId)) {
                        $bId = trim($bId);
                        $packagingIds[] = $bId;
                        if (!$isConsumed && isset($consumedBoxes[$bId])) {
                            $isConsumed = true;
                            $consumedByDo = $consumedBoxes[$bId]->do_number;
                        }
                    }
                }
            }

            if (empty($packagingIds) && !empty($r->pl_number)) {
                $packagingIds[] = $r->pl_number;
            }

            $rArray['packaging_ids'] = $packagingIds;
            $rArray['is_consumed'] = $isConsumed;
            $rArray['consumed_by_do'] = $consumedByDo;

            return $rArray;
        });

        $settings = DoSetting::first();
        $currentBatch = max(1, (int)($settings?->current_batch_number ?: 1));
        $plCount = DB::table('standalone_packing_labels')->where('batch_number', $currentBatch)->count();
        $nextPlSeq = $plCount + 1;
        $sjCount = DeliveryOrder::where('batch_number', $currentBatch)->count();
        $nextSjSeq = $sjCount + 1;

        return response()->json([
            'ok' => true,
            'data' => $data,
            'current_batch' => $currentBatch,
            'batch_number' => $currentBatch,
            'next_pl_seq' => $nextPlSeq,
            'next_sj_seq' => $nextSjSeq,
            'next_batch' => $currentBatch,
        ]);
    }

    /**
     * Detail Standalone Packing Label
     */
    public function getPackingLabelDetail(Request $request)
    {
        $id = $request->input('id');
        $row = DB::table('standalone_packing_labels')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Data label tidak ditemukan'], 404);
        }

        $data = (array) $row;
        $settings = DoSetting::first();
        $data['crates'] = json_decode($row->crates_json ?? '[]', true) ?: [];
        $data['contract_number'] = $settings?->default_contract_number ?: 'TRAK/2509/APBN/XI/2025/KEMHAN';
        $data['program_name'] = $settings?->default_program_name ?: 'PENGADAAN ALPALHANKAM MATSUSSUS';
        $data['customer_name'] = $settings?->default_customer_name ?: 'KEMHAN RI';
        $data['customer_address'] = $settings?->default_customer_address ?: "BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 - 7\nJAKARTA, 10110";
        $data['do_date'] = $row->label_date;
        $data['do_number'] = $row->pl_number;
        $data['product_name'] = $row->product_name ?: ($settings?->default_product_name ?: 'Nanggala P9');
        $data['created_by_name'] = $row->created_by ?: ($settings?->default_issuer_name ?: 'Rezka Rachdana');
        $data['created_by_title'] = $settings?->default_issuer_title ?: 'Staf PPIC';

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * Simpan Standalone Packing Label
     */
    public function createPackingLabel(Request $request)
    {
        $pkgType = trim($request->input('package_type', 'crate'));
        if (!in_array($pkgType, ['crate', 'box'])) {
            $pkgType = 'crate';
        }
        $settings = DoSetting::first();
        $batchNum = max(1, (int)($request->input('batch_number') ?: ($settings?->current_batch_number ?: 1)));
        $prodName = trim($request->input('product_name', 'Nanggala P9'));
        $notes = trim($request->input('notes', ''));
        $createdBy = trim($request->input('created_by', auth()->user()?->full_name ?? 'PPIC'));
        $cratesData = $request->input('crates', []);

        $dateStr = $request->input('date') ?: $request->input('pl_date') ?: now()->format('Y-m-d');
        $targetDt = \Carbon\Carbon::parse($dateStr);
        $targetDayStr = $targetDt->format('Ymd');
        $mmYyyy = $targetDt->format('m/Y');

        // Kumpulkan ID Peti / ID Dus dari payload
        $payloadPackagingIds = [];
        foreach ($cratesData as $c) {
            if ($pkgType === 'crate' && !empty($c['crate_id'])) {
                $payloadPackagingIds[] = trim($c['crate_id']);
            } elseif ($pkgType === 'box') {
                $bId = $c['box_id'] ?? ($c['boxes'][0]['box_id'] ?? null);
                if (!empty($bId)) {
                    $payloadPackagingIds[] = trim($bId);
                }
            }
        }

        // Validasi Keunikan ID Peti / ID Dus di Surat Jalan (DO)
        if ($pkgType === 'crate') {
            foreach ($payloadPackagingIds as $pId) {
                if (DB::table('packing_lists')->where('crate_id', $pId)->exists()) {
                    return response()->json([
                        'ok' => false,
                        'error' => "ID Peti {$pId} sudah pernah terdaftar pada Surat Jalan lain!"
                    ], 422);
                }
            }
        } else {
            foreach ($payloadPackagingIds as $pId) {
                if (DB::table('packing_list_boxes')->where('box_id', $pId)->exists()) {
                    return response()->json([
                        'ok' => false,
                        'error' => "ID Dus {$pId} sudah pernah terdaftar pada Surat Jalan lain!"
                    ], 422);
                }
            }
        }

        // Cek juga pada label kemasan yang sudah tersimpan
        $existingLabels = DB::table('standalone_packing_labels')->get(['id', 'crates_json']);
        foreach ($existingLabels as $ex) {
            $exCrates = json_decode($ex->crates_json ?? '[]', true) ?: [];
            foreach ($exCrates as $ec) {
                $ecId = ($pkgType === 'crate') ? ($ec['crate_id'] ?? null) : ($ec['box_id'] ?? ($ec['boxes'][0]['box_id'] ?? null));
                if ($ecId && in_array(trim($ecId), $payloadPackagingIds)) {
                    return response()->json([
                        'ok' => false,
                        'error' => "ID Kemasan {$ecId} sudah pernah dibuat pada riwayat label lain! Silakan sesuaikan nomor batch atau pengiriman."
                    ], 422);
                }
            }
        }

        $primaryId = !empty($payloadPackagingIds) ? $payloadPackagingIds[0] : sprintf("PK-%s-%02d", $targetDayStr, $batchNum);
        $plNumber = $primaryId;
        $labelBatchId = $primaryId;

        // VALIDASI KETAT: Seluruh SN wajib lolos alur proses dan tidak boleh ada duplikasi
        $allSns = [];
        foreach ($cratesData as $c) {
            foreach ($c['boxes'] ?? [] as $b) {
                foreach ($b['items'] ?? [] as $it) {
                    $sVal = is_array($it) ? ($it['serial_number'] ?? '') : (string)$it;
                    $sVal = strtoupper(trim($sVal));
                    if (empty($sVal)) {
                        return response()->json([
                            'ok' => false,
                            'error' => 'Seluruh slot Serial Number wajib diisi lengkap sebelum label dapat disimpan!'
                        ], 400);
                    }
                    $allSns[] = $sVal;
                }
            }
        }

        if (empty($allSns)) {
            return response()->json([
                'ok' => false,
                'error' => 'Tidak ada data Serial Number yang dikirimkan.'
            ], 400);
        }

        // 1. Validasi Anti-Duplikasi dalam form label ini
        $seen = [];
        foreach ($allSns as $sn) {
            if (isset($seen[$sn])) {
                return response()->json([
                    'ok' => false,
                    'error' => "Nomor seri {$sn} terduplikasi di dalam dokumen packing list ini! Tidak boleh ada part number yang diinput dua kali."
                ], 400);
            }
            $seen[$sn] = true;
        }

        // 2. Validasi Anti-Duplikasi dengan Surat Jalan (DO) yang sudah ada
        $alreadyInDo = PackingListItem::whereIn('serial_number', $allSns)->first();
        if ($alreadyInDo) {
            return response()->json([
                'ok' => false,
                'error' => "Nomor seri {$alreadyInDo->serial_number} sudah pernah digunakan pada Surat Jalan ID #{$alreadyInDo->delivery_order_id}!"
            ], 400);
        }

        // 3. Validasi Anti-Duplikasi dengan Standalone Packing Labels lain
        $existingStandalone = DB::table('standalone_packing_labels')->pluck('crates_json', 'pl_number');
        foreach ($existingStandalone as $plNum => $cj) {
            $cratesJsonArr = json_decode($cj, true) ?: [];
            foreach ($cratesJsonArr as $cjCrate) {
                foreach ($cjCrate['boxes'] ?? [] as $cjBox) {
                    foreach ($cjBox['items'] ?? [] as $cjItem) {
                        $exSn = strtoupper(trim(is_array($cjItem) ? ($cjItem['serial_number'] ?? '') : (string)$cjItem));
                        if (in_array($exSn, $allSns)) {
                            return response()->json([
                                'ok' => false,
                                'error' => "Nomor seri {$exSn} sudah pernah digunakan pada Dokumen Packing List {$plNum}!"
                            ], 400);
                        }
                    }
                }
            }
        }

        // 4. Validasi Kepatuhan Alur: Wajib telah melewati seluruh alur sampai Penerimaan Fisik Gudang (ACC)
        // Baik yang langsung Good maupun yang setelah Hold / Dirakit Ulang telah diverifikasi Admin hingga ACC Gudang
        foreach ($allSns as $sn) {
            $valid = DB::table('assemblies as a')
                ->where('a.part15_sn', $sn)
                ->where(function ($w) {
                    $w->where('a.status', 'Good')
                      ->orWhere('a.status', 'Dirakit Ulang')
                      ->orWhere('a.status', 'Selesai')
                      ->orWhereIn('a.verified_status', ['APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'APPROVED_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_PROCEED_TF1', 'APPROVED_PROCEED_TF2', 'ACC']);
                })
                ->where(function ($w) {
                    $w->where('a.assy_test_status', 'Good')
                      ->orWhere('a.assy_test_status', 'Pass')
                      ->orWhere('a.assy_test_status', 'Lolos')
                      ->orWhereIn('a.verified_status', ['APPROVED_QC', 'APPROVED_PROCEED_TF2', 'APPROVED_PROCEED_QC', 'ACC', 'APPROVED']);
                })
                ->where(function ($w) {
                    $w->whereIn(DB::raw("UPPER(COALESCE(a.qc_status, ''))"), ['GOOD', 'GOOD_QC', 'PASS', 'ACC'])
                      ->orWhereIn('a.verified_status', ['APPROVED_QC', 'ACC', 'APPROVED']);
                })
                ->where(function ($w) {
                    $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
                })
                ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
                ->exists();

            if (!$valid) {
                return response()->json([
                    'ok' => false,
                    'error' => "Nomor seri {$sn} tidak sah! Unit harus telah menyelesaikan seluruh proses sampai dengan Penerimaan Fisik Gudang (ACC) dan tidak tertahan."
                ], 400);
            }
        }

        // Hitung total unit dan container
        $totalQty = 0;
        foreach ($cratesData as &$c) {
            $boxes = $c['boxes'] ?? [];
            $cQty = 0;
            foreach ($boxes as &$b) {
                $items = $b['items'] ?? [];
                foreach ($items as $it) {
                    $sVal = is_array($it) ? ($it['serial_number'] ?? '') : (string)$it;
                    if (!empty(trim($sVal))) {
                        $cQty++;
                    }
                }
            }
            if ($cQty > 0) {
                $c['total_quantity'] = $cQty;
            } elseif (empty($c['total_quantity'])) {
                $c['total_quantity'] = ($pkgType === 'crate') ? 20 : 10;
            }
            $totalQty += $c['total_quantity'];
        }
        unset($c);

        $totalContainers = count($cratesData);

        $plMode = strtolower(trim((string)$request->input('pl_mode', '')));
        $isBackdate = $request->boolean('is_backdate') || ($plMode === 'backdate') || ($targetDt->toDateString() < now()->toDateString());
        if (!$plMode) {
            $plMode = $isBackdate ? 'backdate' : 'realtime';
        }

        $id = DB::table('standalone_packing_labels')->insertGetId([
            'label_batch_id' => $labelBatchId,
            'pl_number' => $plNumber,
            'label_date' => $targetDt->toDateString(),
            'is_backdate' => $isBackdate ? 1 : 0,
            'pl_mode' => $plMode,
            'package_type' => $pkgType,
            'batch_number' => $batchNum,
            'product_name' => $prodName,
            'total_quantity' => $totalQty,
            'total_containers' => $totalContainers,
            'crates_json' => json_encode($cratesData),
            'notes' => $notes,
            'created_by' => $createdBy,
            'created_at' => $isBackdate ? $targetDt->toDateTimeString() : now(),
            'updated_at' => now(),
        ]);

        $maxBatch = DB::table('delivery_orders')->max('batch_number') ?? 0;
        $maxStandalone = DB::table('standalone_packing_labels')->max('batch_number') ?? 0;
        $nextBatch = max((int)$maxBatch, (int)$maxStandalone) + 1;

        return response()->json([
            'ok' => true,
            'id' => $id,
            'label_batch_id' => $labelBatchId,
            'pl_number' => $plNumber,
            'crates' => $cratesData,
            'next_batch' => $nextBatch,
            'packaging_ids' => $payloadPackagingIds,
            'message' => "Label kemasan " . ($pkgType === 'crate' ? 'Peti' : 'Dus') . " {$primaryId} berhasil disimpan!"
        ]);
    }

    /**
     * Hapus Standalone Packing Label
     */
    public function deletePackingLabel(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json(['ok' => false, 'error' => 'ID label wajib diberikan'], 400);
        }

        $label = DB::table('standalone_packing_labels')->where('id', $id)->first();
        if (!$label) {
            return response()->json(['ok' => false, 'error' => 'Data label tidak ditemukan'], 404);
        }
        if ($label->is_consumed || $label->delivery_order_id) {
            return response()->json(['ok' => false, 'error' => 'Dokumen Packing List ini sudah dikonsumsi oleh Surat Jalan dan tidak dapat dihapus!'], 422);
        }

        DB::table('standalone_packing_labels')->where('id', $id)->delete();
        return response()->json(['ok' => true, 'message' => 'Label Packing List berhasil dihapus!']);
    }

    /**
     * Test Koneksi Cloudinary
     */
    public function cloudinaryTest(Request $request)
    {
        $url = env('CLOUDINARY_URL', '');
        return response()->json([
            'ok' => true,
            'message' => 'Layanan Cloudinary siap.',
            'configured' => !empty($url),
        ]);
    }
}


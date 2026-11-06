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
        $available = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->join('master_stocks as m15', function ($join) {
                $join->on('a.part15_sn', '=', 'm15.sn')
                     ->where('m15.part_type', '=', '15');
            })
            ->where('a.status', 'Good')
            ->where('a.assy_test_status', 'Good')
            ->where('a.qc_status', 'GOOD')
            ->where(function ($w) {
                $w->whereNull('a.is_declined')->orWhere('a.is_declined', 0);
            })
            ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
            ->whereIn(DB::raw("UPPER(COALESCE(a.verified_status, ''))"), ['ACC', 'ACCEPTED'])
            ->whereNotIn('m15.status', ['Tersedia', 'Scrap', 'Part Diganti', 'Hold', 'Declined', 'Decline'])
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

        // Cari nomor urut terakhir pada bulan & tahun ini
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

        $batchNum = (int)$request->input('batch_number', 1);
        $batch2Digit = str_pad($batchNum, 2, '0', STR_PAD_LEFT);
        $pp2Digit = str_pad($seq, 2, '0', STR_PAD_LEFT);
        $yymm = $date->format('y') . $month;

        // Format Standar Baru: YYMM-BB-PPDD (Peti) & YYMM-BB-PPDD-SS (Dus)
        $cratePrefix = "{$yymm}-{$batch2Digit}-{$pp2Digit}";
        $nextBatchNumber = "{$yymm}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

        return $this->successResponse([
            'next_number' => $nextDoNumber,
            'next_pl_number' => $nextPlNumber,
            'next_batch' => $nextBatchNumber,
            'sequence' => $seq,
            'delivery_seq' => $seq,
            'batch_number_formatted' => $batch2Digit,
            'crate_prefix' => $cratePrefix,
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
        $request->validate([
            'do_number' => 'required|string|unique:delivery_orders,do_number',
            'do_date' => 'required|date',
            'customer_name' => 'required|string',
            'crates' => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $crates = $request->input('crates', []);
            $totalCrates = count($crates);
            $totalBoxes = 0;
            $totalUnits = 0;

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

                            // Anti-duplikasi dengan Standalone Packing Label yang tersimpan
                            $existingLabels = DB::table('standalone_packing_labels')->pluck('crates_json', 'pl_number');
                            foreach ($existingLabels as $plNum => $cj) {
                                $cratesJsonArr = json_decode($cj, true) ?: [];
                                foreach ($cratesJsonArr as $cjCrate) {
                                    foreach ($cjCrate['boxes'] ?? [] as $cjBox) {
                                        foreach ($cjBox['items'] ?? [] as $cjItem) {
                                            $exSn = strtoupper(trim(is_array($cjItem) ? ($cjItem['serial_number'] ?? '') : (string)$cjItem));
                                            if ($exSn === strtoupper($sn)) {
                                                return response()->json([
                                                    'ok' => false,
                                                    'error' => "Nomor seri {$sn} sudah pernah terdaftar pada Dokumen Packing List {$plNum}!"
                                                ], 400);
                                            }
                                        }
                                    }
                                }
                            }

                            // 3. Validasi kepatuhan alur: Wajib lolos QC GOOD dan telah di-ACCEPT fisik oleh Gudang
                            $validUnit = DB::table('assemblies as a')
                                ->join('master_stocks as m15', function ($j) {
                                    $j->on('a.part15_sn', '=', 'm15.sn')->where('m15.part_type', '15');
                                })
                                ->where('a.part15_sn', $sn)
                                ->where('a.qc_status', 'GOOD')
                                ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
                                ->whereNotIn('m15.status', ['Tersedia', 'Scrap', 'Part Diganti', 'Hold', 'Declined', 'Decline'])
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

            $do = DeliveryOrder::create([
                'do_number' => trim($request->do_number),
                'pl_number' => $request->input('pl_number') ?: str_replace('/SJ/', '/PL/', $request->do_number),
                'do_date' => $request->do_date,
                'customer_name' => trim($request->customer_name),
                'customer_address' => $request->input('customer_address'),
                'customer_phone' => $request->input('customer_phone'),
                'customer_fax' => $request->input('customer_fax'),
                'package_type' => $request->input('package_type', 'peti'),
                'total_crates' => $totalCrates,
                'total_boxes' => $totalBoxes,
                'total_units' => $totalUnits,
                'status' => 'ISSUED',
                'created_by' => auth()->user()?->username ?: 'PPIC',
                'created_by_name' => auth()->user()?->full_name ?: 'PPIC Staff',
                'notes' => $request->input('notes'),
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

        $query = DB::table('delivery_orders as d')
            ->join('packing_list_items as pli', 'd.id', '=', 'pli.delivery_order_id')
            ->leftJoin('assemblies as a', 'pli.serial_number', '=', 'a.part15_sn')
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
     * Tambah Customer Baru
     */
    public function createCustomer(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        $cust = Customer::create([
            'name' => trim($request->input('name')),
            'address' => trim($request->input('address', '')),
            'phone' => trim($request->input('phone', '')),
            'fax' => trim($request->input('fax', '')),
        ]);
        return $this->successResponse($cust, 'Customer berhasil ditambahkan.');
    }

    /**
     * Perbarui Customer
     */
    public function updateCustomer(Request $request)
    {
        $id = $request->input('id');
        $cust = Customer::findOrFail($id);
        $cust->update([
            'name' => trim($request->input('name', $cust->name)),
            'address' => trim($request->input('address', $cust->address)),
            'phone' => trim($request->input('phone', $cust->phone)),
            'fax' => trim($request->input('fax', $cust->fax)),
        ]);
        return $this->successResponse($cust, 'Customer berhasil diperbarui.');
    }

    /**
     * Hapus Customer
     */
    public function deleteCustomer(Request $request)
    {
        $id = $request->input('id');
        Customer::where('id', $id)->delete();
        return $this->successResponse(null, 'Customer berhasil dihapus.');
    }

    /**
     * Mengambil daftar Standalone Packing Labels
     */
    public function getPackingLabels()
    {
        $rows = DB::table('standalone_packing_labels')
            ->orderBy('id', 'desc')
            ->get();

        $data = $rows->map(function ($r) {
            $rArray = (array) $r;
            $rArray['crates'] = json_decode($r->crates_json ?? '[]', true) ?: [];
            return $rArray;
        });

        $maxBatch = DB::table('delivery_orders')->max('batch_number') ?? 0;
        $maxStandalone = DB::table('standalone_packing_labels')->max('batch_number') ?? 0;
        $nextBatch = max((int)$maxBatch, (int)$maxStandalone) + 1;

        return response()->json([
            'ok' => true,
            'data' => $data,
            'next_batch' => $nextBatch,
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
        $data['crates'] = json_decode($row->crates_json ?? '[]', true) ?: [];

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * Simpan Standalone Packing Label
     */
    public function createPackingLabel(Request $request)
    {
        $pkgType = trim($request->input('package_type', 'crate'));
        $batchNum = (int) $request->input('batch_number', 1);
        $prodName = trim($request->input('product_name', 'Nanggala P9'));
        $notes = trim($request->input('notes', ''));
        $createdBy = trim($request->input('created_by', auth()->user()?->full_name ?? 'PPIC'));
        $cratesData = $request->input('crates', []);

        $dateStr = $request->input('date') ?: $request->input('pl_date') ?: now()->format('Y-m-d');
        $targetDt = \Carbon\Carbon::parse($dateStr);
        $targetDayStr = $targetDt->format('Ymd');
        $mmYyyy = $targetDt->format('m/Y');

        // Cari max label_batch_id pada tanggal target
        $existingLabels = DB::table('standalone_packing_labels')
            ->where('label_batch_id', 'like', "LBL-{$targetDayStr}-%")
            ->pluck('label_batch_id');

        $maxSeq = 0;
        foreach ($existingLabels as $lid) {
            if (preg_match('/LBL-\d+-(\d+)/', $lid, $m)) {
                $maxSeq = max($maxSeq, (int)$m[1]);
            }
        }
        $nextLblSeq = $maxSeq + 1;
        $labelBatchId = sprintf("LBL-%s-%03d", $targetDayStr, $nextLblSeq);
        while (DB::table('standalone_packing_labels')->where('label_batch_id', $labelBatchId)->exists()) {
            $nextLblSeq++;
            $labelBatchId = sprintf("LBL-%s-%03d", $targetDayStr, $nextLblSeq);
        }

        // Cari nomor urut PL berikutnya
        $rowsPl = DB::table('packing_lists')->where('pl_number', 'like', "%/PL/PPIC/{$mmYyyy}")->pluck('pl_number')->toArray();
        $rowsLbl = DB::table('standalone_packing_labels')->where('pl_number', 'like', "%/PL/PPIC/{$mmYyyy}")->pluck('pl_number')->toArray();

        $maxPlSeq = 0;
        foreach (array_merge($rowsPl, $rowsLbl) as $pn) {
            if (preg_match('/^(\d+)\//', $pn, $m)) {
                $maxPlSeq = max($maxPlSeq, (int)$m[1]);
            }
        }
        $plSeq = $maxPlSeq + 1;
        $plNumber = sprintf("%03d/PL/PPIC/%s", $plSeq, $mmYyyy);
        while (DB::table('packing_lists')->where('pl_number', $plNumber)->exists() || DB::table('standalone_packing_labels')->where('pl_number', $plNumber)->exists()) {
            $plSeq++;
            $plNumber = sprintf("%03d/PL/PPIC/%s", $plSeq, $mmYyyy);
        }

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

        // 4. Validasi Kepatuhan Alur: Wajib lolos Meja Rakit -> TF 1 -> QC -> Penerimaan Fisik Gudang (ACC)
        foreach ($allSns as $sn) {
            $valid = DB::table('assemblies as a')
                ->where('a.part15_sn', $sn)
                ->where('a.status', 'Good')
                ->where('a.assy_test_status', 'Good')
                ->where('a.qc_status', 'GOOD')
                ->whereIn(DB::raw("UPPER(COALESCE(a.warehouse_receipt_status, ''))"), ['ACCEPTED', 'ACC'])
                ->whereIn(DB::raw("UPPER(COALESCE(a.verified_status, ''))"), ['ACC', 'ACCEPTED'])
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

        $id = DB::table('standalone_packing_labels')->insertGetId([
            'label_batch_id' => $labelBatchId,
            'pl_number' => $plNumber,
            'label_date' => $targetDt->toDateString(),
            'package_type' => $pkgType,
            'batch_number' => $batchNum,
            'product_name' => $prodName,
            'total_quantity' => $totalQty,
            'total_containers' => $totalContainers,
            'crates_json' => json_encode($cratesData),
            'notes' => $notes,
            'created_by' => $createdBy,
            'created_at' => $targetDt->toDateTimeString(),
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
            'message' => "Label Packing List {$labelBatchId} berhasil dibuat dan disimpan!"
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


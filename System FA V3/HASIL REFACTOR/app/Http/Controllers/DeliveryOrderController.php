<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryOrder;
use App\Models\PackingList;
use App\Models\PackingListBox;
use App\Models\PackingListItem;
use App\Models\DoSetting;
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
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $docFilter = $request->input('doc_number');

        $query = DeliveryOrder::with('packingLists.boxes.items')
            ->orderBy('id', 'desc');

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('do_number', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('pl_number', 'like', "%{$q}%");
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
     * Serial Number yang sudah Lulus TF 2 (QC) dan Siap untuk Delivery Order
     */
    public function availableSerialNumbers()
    {
        // Ambil unit yang sudah lulus GOOD di QC (TF 2) dan belum masuk DO
        $usedSns = PackingListItem::pluck('serial_number')->toArray();

        $available = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->where('a.qc_status', 'GOOD')
            ->whereNotIn('a.part15_sn', $usedSns)
            ->select([
                'a.part15_sn as serial_number',
                'a.part1_sn',
                'a.part12_sn',
                't.tray_code',
                'a.set_number',
                'a.qc_tested_at',
            ])
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
        $nextBatchNumber = "{$date->format('y')}{$month}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

        return $this->successResponse([
            'next_number' => $nextDoNumber,
            'next_pl_number' => $nextPlNumber,
            'next_batch' => $nextBatchNumber,
            'sequence' => $seq,
            'crate_prefix' => $cratePrefix,
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

            foreach ($crates as $c) {
                $boxes = $c['boxes'] ?? [];
                $totalBoxes += count($boxes);
                foreach ($boxes as $b) {
                    $items = $b['items'] ?? [];
                    $totalUnits += count(array_filter($items, fn($it) => !empty($it['serial_number'])));
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
}

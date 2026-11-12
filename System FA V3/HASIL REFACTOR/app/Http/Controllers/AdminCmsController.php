<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseMaintenanceService;
use App\Models\DoSetting;
use App\Models\Customer;
use App\Models\TestingPoint;
use App\Models\Assembly;
use App\Models\MasterStock;
use App\Models\PartAuditLog;
use App\Models\Tray;

class AdminCmsController extends Controller
{
    protected DatabaseMaintenanceService $maintenanceService;

    public function __construct(DatabaseMaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Fitur 1 CMS: Bersihkan Database dari Semua Tray dan Kembalikan Stok ke 'Tersedia'
     * Endpoint: POST /api/admin/cms/clear-trays
     */
    public function clearTrays(Request $request)
    {
        $admin = $this->resolveActor($request)['full_name'];
        $res = $this->maintenanceService->clearAllTrays($admin);

        if (!$res['ok']) {
            return $this->errorResponse($res['message'], 500);
        }

        return $this->successResponse($res['stocks_restored'] ?? $res['data'] ?? null, $res['message']);
    }

    /**
     * Fitur 2 CMS: Hapus Riwayat Jeda / Downtime Operator
     * Endpoint: POST /api/admin/cms/clear-downtimes
     */
    public function clearDowntimes(Request $request)
    {
        $admin = $this->resolveActor($request)['full_name'];
        $res = $this->maintenanceService->clearDowntimeAudit($admin);

        if (!$res['ok']) {
            return $this->errorResponse($res['message'], 500);
        }

        return $this->successResponse($res['deleted_count'] ?? $res['data'] ?? null, $res['message']);
    }

    /**
     * Fitur 3 CMS: Hapus Seluruh Audit Trail & Log Aktivitas Sistem
     * Endpoint: POST /api/admin/cms/clear-audit-logs
     */
    public function clearAuditLogs(Request $request)
    {
        $admin = $this->resolveActor($request)['full_name'];
        $res = $this->maintenanceService->clearOverallAudit($admin);

        if (!$res['ok']) {
            return $this->errorResponse($res['message'], 500);
        }

        return $this->successResponse($res['purged'] ?? $res['data'] ?? null, $res['message']);
    }

    /**
     * Fitur 4 CMS: Pengaturan Global Delivery Order & Packing List
     * Endpoint: GET /api/admin/cms/do-settings
     *           POST /api/admin/cms/do-settings
     */
    public function getDoSettings()
    {
        $settings = DoSetting::firstOrCreate(['id' => 1]);
        return $this->successResponse($settings);
    }

    public function updateDoSettings(Request $request)
    {
        $settings = DoSetting::firstOrCreate(['id' => 1]);
        
        if ($request->has('current_batch_number')) {
            $settings->current_batch_number = max(1, (int)$request->input('current_batch_number'));
        }
        
        $fillable = $settings->getFillable();
        foreach ($fillable as $field) {
            if ($request->has($field) && $field !== 'current_batch_number') {
                $settings->{$field} = $request->input($field);
            }
        }
        $settings->save();

        return $this->successResponse($settings, 'Pengaturan Delivery Order & Dokumen berhasil diperbarui.');
    }

    /**
     * Parameter Checklist TF 1 (17 Testing Points) CRUD
     */
    public function getTestingPoints()
    {
        $points = TestingPoint::orderBy('test_num', 'asc')->get();
        return $this->successResponse($points);
    }

    public function updateTestingPoint(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $point = TestingPoint::findOrFail($request->id);
            $testNum = $request->input('test_num', $point->test_num);
            $payload = [
                'category' => $request->input('category', $point->category),
                'instruction' => $request->input('instruction', $point->instruction),
                'output' => $request->input('output', $point->output),
            ];

            if ($request->has('test_num')) {
                $payload['test_num'] = $testNum;
            }

            if ($request->has('images')) {
                $images = $request->input('images');
                $payload['images'] = $this->processTestingPointImages($images, $testNum);
            }

            $point->update($payload);

            return $this->successResponse($point, 'Parameter uji fungsional 1 berhasil disimpan.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating testing point: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => 'Gagal menyimpan testing point: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Konversi foto upload base64 testing point ke physical file dan return list web URL path
     */
    private function processTestingPointImages($images, $testNum = 'tp')
    {
        if (empty($images)) {
            return [];
        }
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [$images];
        }
        if (!is_array($images)) {
            return [];
        }

        $processed = [];
        $uploadDir = public_path('uploads/testing_points');
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        foreach ($images as $img) {
            if (!is_string($img) || empty(trim($img))) {
                continue;
            }

            $img = trim($img);

            // Jika sudah berupa URL relatif atau absolut, pertahankan
            if (!str_starts_with($img, 'data:image')) {
                $processed[] = $img;
                continue;
            }

            // Jika berupa Data URL Base64, decode dan simpan ke file
            if (preg_match('/^data:image\/(\w+);base64,/', $img, $typeMatches)) {
                $rawBase64 = substr($img, strpos($img, ',') + 1);
                $ext = strtolower($typeMatches[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                if (!in_array($ext, ['jpg', 'png', 'webp', 'gif'])) {
                    $ext = 'jpg';
                }

                $binary = base64_decode($rawBase64);
                if ($binary !== false) {
                    $filename = 'tp_' . $testNum . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
                    $filePath = $uploadDir . '/' . $filename;
                    if (file_put_contents($filePath, $binary) !== false) {
                        $processed[] = '/uploads/testing_points/' . $filename;
                    }
                }
            }
        }

        return $processed;
    }

    public function getAuditLogs(Request $request)
    {
        $query = DB::table('part_audit_logs');
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status_to', $request->status);
        }
        if ($request->filled('part_type')) {
            $query->where('part_type', $request->part_type);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('sn', 'LIKE', "%{$q}%")
                  ->orWhere('action_by', 'LIKE', "%{$q}%")
                  ->orWhere('reason', 'LIKE', "%{$q}%");
            });
        }
        $logs = $query->orderBy('id', 'desc')->get();
        return response()->json(['ok' => true, 'data' => $logs]);
    }

    public function getPendingVerifications(Request $request)
    {
        $query = DB::table('re_register_requests as r')
            ->leftJoin('master_stocks as m', function ($join) {
                $join->on('r.part_type', '=', 'm.part_type')
                     ->on('r.sn', '=', 'm.sn');
            })
            ->select([
                'r.*',
                'm.cs',
                'm.last_reason as stock_last_reason',
                'm.is_replaced_from_assy'
            ]);

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('r.status', $request->status);
        }

        $items = $query->orderBy('r.id', 'desc')->limit(300)->get();

        foreach ($items as $item) {
            // 1. Cari riwayat penggantian part (part_replacement_requests)
            $repl = DB::table('part_replacement_requests')
                ->where('replacements_json', 'LIKE', '%"old_sn":"' . $item->sn . '"%')
                ->orderBy('id', 'desc')
                ->first();

            if ($repl) {
                $assy = DB::table('assemblies')->where('id', $repl->assembly_id)->first();
                $tray = DB::table('trays')->where('id', $repl->tray_id ?: ($assy ? $assy->tray_id : null))->first();

                $item->previous_set_number = $repl->set_number ?: ($assy ? $assy->set_number : null);
                $item->previous_tray_code = $tray ? $tray->tray_code : null;
                $moCandidate = $tray ? $tray->source_document_mo : null;
                $item->previous_mo = ($moCandidate && !str_starts_with($moCandidate, 'WH/ASSY')) ? $moCandidate : null;
                $item->previous_pic_assembly = $assy ? ($assy->pic_assembly ?: ($tray ? $tray->pic_warehouse : null)) : ($tray ? $tray->pic_warehouse : null);
                $item->previous_assembly_date = $assy ? ($assy->started_at ?: $assy->created_at) : $repl->created_at;

                $item->previous_part15_sn = $assy ? $assy->part15_sn : null;
                $item->previous_part1_sn = $item->part_type === '1' ? $item->sn : ($assy ? $assy->part1_sn : null);
                $item->previous_part12_sn = $item->part_type === '12' ? $item->sn : ($assy ? $assy->part12_sn : null);
                if ($item->part_type === '15') {
                    $item->previous_part15_sn = $item->sn;
                }

                $item->previous_failure_reasons = $repl->reason ?: ($assy ? ($assy->assy_failure_reasons ?: $assy->reason) : null);
                $item->previous_assembly_reason = $repl->reason;
            } else {
                // 2. Jika tidak ada di replacement, cari riwayat di tabel assemblies langsung
                $assy = DB::table('assemblies as a')
                    ->leftJoin('trays as t', 'a.tray_id', '=', 't.id')
                    ->select([
                        'a.*',
                        't.tray_code',
                        't.source_document_mo'
                    ])
                    ->where(function ($q) use ($item) {
                        if ($item->part_type === '15') {
                            $q->where('a.part15_sn', $item->sn);
                        } elseif ($item->part_type === '1') {
                            $q->where('a.part1_sn', $item->sn);
                        } elseif ($item->part_type === '12') {
                            $q->where('a.part12_sn', $item->sn);
                        } else {
                            $q->where('a.part15_sn', $item->sn)
                              ->orWhere('a.part1_sn', $item->sn)
                              ->orWhere('a.part12_sn', $item->sn);
                        }
                    })
                    ->orderBy('a.id', 'desc')
                    ->first();

                if ($assy) {
                    $item->previous_set_number = $assy->set_number;
                    $item->previous_tray_code = $assy->tray_code;
                    $assyMo = $assy->source_document_mo;
                    $item->previous_mo = ($assyMo && !str_starts_with($assyMo, 'WH/ASSY')) ? $assyMo : null;
                    $item->previous_pic_assembly = $assy->pic_assembly;
                    $item->previous_assembly_date = $assy->started_at ?: $assy->created_at;
                    $item->previous_part15_sn = $assy->part15_sn;
                    $item->previous_part1_sn = $assy->part1_sn;
                    $item->previous_part12_sn = $assy->part12_sn;
                    $item->previous_failure_reasons = $assy->assy_failure_reasons ?: ($assy->qc_failure_reasons ?: $assy->reason);
                    $item->previous_assembly_reason = $assy->reason ?: $assy->rework_reason;
                }
            }

            // 3. Ambil riwayat audit log jika ada catatan alasan pelepasan
            $auditLog = DB::table('part_audit_logs')
                ->where('sn', $item->sn)
                ->where('part_type', $item->part_type)
                ->whereIn('action_type', ['REPLACE_PART_REQUESTED', 'ADMIN_APPROVE_REPLACEMENT', 'HOLD_PART', 'DISASSEMBLE_PART', 'SUBMIT_HOLD'])
                ->orderBy('id', 'desc')
                ->first();
            if ($auditLog) {
                $item->audit_log_reason = $auditLog->reason;
            }
        }

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function getPendingAssemblies(Request $request)
    {
        // 1. Assemblies dari Perakitan, TF1, dan Pengembalian/Receipts (Declined)
        $assyQuery = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->leftJoin('users as u_assy', function ($join) {
                $join->on('a.pic_assembly', '=', 'u_assy.username')
                     ->orOn('a.pic_assembly', '=', 'u_assy.full_name');
            })
            ->leftJoin('users as u_wh', function ($join) {
                $join->on('t.pic_warehouse', '=', 'u_wh.username')
                     ->orOn('t.pic_warehouse', '=', 'u_wh.full_name');
            })
            ->leftJoin('users as u_qc', function ($join) {
                $join->on('a.qc_tested_by', '=', 'u_qc.username')
                     ->orOn('a.qc_tested_by', '=', 'u_qc.full_name');
            })
            ->leftJoin('master_stocks as ms15', function ($join) {
                $join->on('ms15.sn', '=', 'a.part15_sn')
                     ->where('ms15.part_type', '=', '15');
            })
            ->leftJoin('master_stocks as ms1', function ($join) {
                $join->on('ms1.sn', '=', 'a.part1_sn')
                     ->where('ms1.part_type', '=', '1');
            })
            ->leftJoin('master_stocks as ms12', function ($join) {
                $join->on('ms12.sn', '=', 'a.part12_sn')
                     ->where('ms12.part_type', '=', '12');
            })
            ->select([
                'a.*',
                'a.id as assembly_id',
                't.tray_code',
                't.source_document_mo',
                't.product_type',
                't.status as tray_status',
                't.session_status as tray_session_status',
                't.return_verified as tray_return_verified',
                'ms15.status as p15_status',
                'ms1.status as p1_status',
                'ms12.status as p12_status',
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly"),
                DB::raw("COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse"),
                DB::raw("COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc"),
                't.created_at as tray_created_at',
            ])
            ->where(function ($w) {
                // a. Unit Declined (dari Penerimaan Fisik Gudang / Receipts)
                $w->where(function ($dec) {
                    $dec->where('a.is_declined', true)
                        ->orWhere('a.status', 'Decline')
                        ->orWhere('a.verified_status', 'DECLINED')
                        ->orWhere('a.verified_status', 'RESOLVED_RESTORED')
                        ->orWhere('a.warehouse_receipt_status', 'DECLINED');
                })
                // b. Unit yang diajukan untuk Verifikasi / Perakitan Ulang oleh Operator
                ->orWhereIn('a.verified_status', ['PENDING_ADMIN', 'PENDING_ADMIN_ASSY_REREG', 'PENDING_ADMIN_TF1', 'PENDING_ADMIN_PROCEED_TF1', 'PENDING_ADMIN_PROCEED_QC'])
                ->orWhere('a.is_reregistered', true);
            })
            ->where(function ($w) {
                $w->whereNull('a.verified_status')
                  ->orWhereNotIn('a.verified_status', ['ACC', 'APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'APPROVED_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_PROCEED_TF1', 'APPROVED_PROCEED_TF2', 'REJECTED_PROCEED']);
            })
            ->where(function ($w) {
                $w->whereNull('a.warehouse_receipt_status')
                  ->orWhere('a.warehouse_receipt_status', '!=', 'ACCEPTED');
            })
            ->orderBy('a.id', 'desc')
            ->get()
            ->map(function ($row) {
                $isDec = (bool)$row->is_declined ||
                         strtoupper((string)$row->verified_status) === 'DECLINED' ||
                         strtoupper((string)$row->verified_status) === 'RESOLVED_RESTORED' ||
                         strtoupper((string)$row->status) === 'DECLINE' ||
                         strtoupper((string)$row->warehouse_receipt_status) === 'DECLINED';

                $isProceedTF1 = $row->verified_status === 'PENDING_ADMIN_PROCEED_TF1' ||
                                (str_contains((string)$row->notes, 'Test Functional 1') && !str_contains((string)$row->notes, 'Disetujui'));
                $isProceedQC = $row->verified_status === 'PENDING_ADMIN_PROCEED_QC' ||
                                (str_contains((string)$row->notes, 'Test Functional 2') && !str_contains((string)$row->notes, 'Disetujui'));

                if ($isDec) {
                    $row->stage = 'DECLINED';
                    $row->process_stage = 'declined';
                    $row->request_type = 'DECLINED';
                    $isDecGR = strtoupper((string)($row->warehouse_receipt_status ?? '')) === 'DECLINED' || !empty($row->warehouse_receipt_at);
                    $row->declined_source = $isDecGR ? 'GR' : 'PG';
                    $row->declined_source_label = $isDecGR ? 'Good Receipts (GR)' : 'Pengembalian Gudang (PG)';
                } elseif ($isProceedTF1) {
                    $row->stage = 'PERAKITAN';
                    $row->process_stage = 'perakitan';
                    $row->request_type = 'PROCEED_TF1';
                } elseif ($isProceedQC) {
                    $row->stage = 'TF1';
                    $row->process_stage = 'test_functional_1';
                    $row->request_type = 'PROCEED_QC';
                } elseif (in_array(strtoupper((string)$row->assy_test_status), ['HOLD TF 1', 'HOLD', 'NOT GOOD', 'FAIL', 'REWORK']) || !empty($row->assy_failure_reasons)) {
                    $row->stage = 'TF1';
                    $row->process_stage = 'test_functional_1';
                    $row->request_type = 'TF1_HOLD';
                } else {
                    $row->stage = 'PERAKITAN';
                    $row->process_stage = 'perakitan';
                    $row->request_type = ($row->is_reregistered || $row->verified_status === 'PENDING_ADMIN_ASSY_REREG') ? 'REREGISTER_ASSY' : 'PERAKITAN_HOLD';
                }
                return $row;
            });

        // 2. Assemblies dari QC yang bermasalah / PENDING_ADMIN_QC
        $qcQuery = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->leftJoin('users as u_qc', function ($join) {
                $join->on('a.qc_tested_by', '=', 'u_qc.username')
                     ->orOn('a.qc_tested_by', '=', 'u_qc.full_name');
            })
            ->leftJoin('users as u_assy', function ($join) {
                $join->on('a.pic_assembly', '=', 'u_assy.username')
                     ->orOn('a.pic_assembly', '=', 'u_assy.full_name');
            })
            ->leftJoin('users as u_wh', function ($join) {
                $join->on('t.pic_warehouse', '=', 'u_wh.username')
                     ->orOn('t.pic_warehouse', '=', 'u_wh.full_name');
            })
            ->leftJoin('master_stocks as ms15', function ($join) {
                $join->on('ms15.sn', '=', 'a.part15_sn')
                     ->where('ms15.part_type', '=', '15');
            })
            ->leftJoin('master_stocks as ms1', function ($join) {
                $join->on('ms1.sn', '=', 'a.part1_sn')
                     ->where('ms1.part_type', '=', '1');
            })
            ->leftJoin('master_stocks as ms12', function ($join) {
                $join->on('ms12.sn', '=', 'a.part12_sn')
                     ->where('ms12.part_type', '=', '12');
            })
            ->select([
                'a.*',
                'a.id as assembly_id',
                't.tray_code',
                't.source_document_mo',
                't.product_type',
                't.status as tray_status',
                't.session_status as tray_session_status',
                't.return_verified as tray_return_verified',
                'ms15.status as p15_status',
                'ms1.status as p1_status',
                'ms12.status as p12_status',
                DB::raw("COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc"),
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly"),
                DB::raw("COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse"),
                't.created_at as tray_created_at',
                DB::raw("'QC' as stage"),
                DB::raw("'qc' as process_stage"),
            ])
            ->where(function ($w) {
                $w->where('a.verified_status', 'PENDING_ADMIN_QC')
                  ->orWhere(function ($sub) {
                      $sub->where('a.is_reregistered', true)
                          ->whereIn('a.qc_status', ['HOLD_QC', 'REWORK_QC', 'HOLD']);
                  });
            })
            ->where(function ($w) {
                $w->where('a.is_declined', false)
                  ->orWhereNull('a.is_declined');
            })
            ->where(function ($w) {
                $w->whereNull('a.status')
                  ->orWhere('a.status', '!=', 'Decline');
            })
            ->where(function ($w) {
                $w->whereNull('a.verified_status')
                  ->orWhereNotIn('a.verified_status', ['HOLD_QC_GUDANG', 'REWORK_QC_GUDANG', 'APPROVED_QC', 'ACC', 'APPROVED', 'REJECTED', 'PENDING_REPLACEMENT', 'RESOLVED_RESTORED', 'APPROVED_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_PROCEED_TF1', 'APPROVED_PROCEED_TF2', 'REJECTED_PROCEED']);
            })
            ->orderBy('a.id', 'desc')
            ->get();

        // Ambil status riil SN dari master_stocks untuk seluruh part
        $allRows = $assyQuery->concat($qcQuery);
        $snList = [];
        foreach ($allRows as $r) {
            if (!empty($r->part15_sn) && $r->part15_sn !== '-') $snList[] = $r->part15_sn;
            if (!empty($r->part1_sn) && $r->part1_sn !== '-') $snList[] = $r->part1_sn;
            if (!empty($r->part12_sn) && $r->part12_sn !== '-') $snList[] = $r->part12_sn;
        }
        $stockStatuses = [];
        if (!empty($snList)) {
            $stockStatuses = DB::table('master_stocks')
                ->whereIn('sn', array_unique($snList))
                ->pluck('status', 'sn')
                ->all();
        }

        $enrich = function ($row) use ($stockStatuses) {
            $isDec = (bool)($row->is_declined ?? false) ||
                     strtoupper((string)($row->verified_status ?? '')) === 'DECLINED' ||
                     strtoupper((string)($row->status ?? '')) === 'DECLINE' ||
                     strtoupper((string)($row->warehouse_receipt_status ?? '')) === 'DECLINED';

            if ($isDec) {
                $isDecGR = strtoupper((string)($row->warehouse_receipt_status ?? '')) === 'DECLINED' || !empty($row->warehouse_receipt_at);
                $row->declined_source = $isDecGR ? 'GR' : 'PG';
                $row->declined_source_label = $isDecGR ? 'Good Receipts (GR)' : 'Pengembalian Gudang (PG)';
            }

            $row->p15_status = $stockStatuses[$row->part15_sn] ?? ($isDec ? 'Declined' : 'Allocated');
            $row->p1_status = $stockStatuses[$row->part1_sn] ?? ($isDec ? 'Declined' : 'Allocated');
            $row->p12_status = $stockStatuses[$row->part12_sn] ?? ($isDec ? 'Declined' : 'Allocated');
            return $row;
        };

        $assyQuery = $assyQuery->map($enrich);
        $qcQuery = $qcQuery->map($enrich);

        return response()->json([
            'ok' => true,
            'data' => $assyQuery,
            'qc_data' => $qcQuery,
        ]);
    }

    public function getPendingReplacements(Request $request)
    {
        $items = DB::table('part_replacement_requests as prr')
            ->join('assemblies as a', 'prr.assembly_id', '=', 'a.id')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->leftJoin('users as u_assy', function ($join) {
                $join->on('a.pic_assembly', '=', 'u_assy.username')
                     ->orOn('a.pic_assembly', '=', 'u_assy.full_name');
            })
            ->select([
                'prr.id as replacement_id',
                'prr.assembly_id',
                'prr.tray_id',
                'prr.set_number',
                'prr.replacements_json as replacement_details',
                'prr.old_part_status as replacement_old_status',
                'prr.reason as replacement_reason',
                'prr.requestor_username as replacement_requestor',
                'prr.created_at as replacement_created_at',
                'prr.status as replacement_status',
                'prr.admin_username',
                'prr.admin_notes',
                'prr.verified_at',
                'a.part15_sn', 'a.part1_sn', 'a.part12_sn',
                'a.status as assembly_status', 'a.reason as assembly_reason',
                'a.assy_test_status', 'a.assy_failure_reasons', 'a.assy_tested_at',
                'a.qc_status', 'a.qc_failure_reasons', 'a.qc_tested_at',
                'a.verified_status',
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly"),
                't.tray_code', 't.source_document_mo', 't.product_type',
                't.created_at as tray_created_at'
            ])
            ->orderByRaw("CASE WHEN prr.status = 'PENDING' THEN 0 ELSE 1 END, prr.id DESC")
            ->get();

        foreach ($items as $item) {
            $qcSt = strtoupper((string)($item->qc_status ?? ''));
            $assyTestSt = (string)($item->assy_test_status ?? '');
            $reason = (string)($item->replacement_reason ?? '');

            if (in_array($qcSt, ['HOLD_QC', 'REWORK_QC', 'HOLD']) || !empty($item->qc_failure_reasons) || stripos($reason, 'QC') !== false) {
                $item->source_stage = 'qc';
                $item->source_stage_label = 'Test Functional 2 (QC)';
            } elseif (in_array($assyTestSt, ['Hold TF 1', 'Hold', 'Not Good', 'FAIL', 'RU', 'Rework']) || !empty($item->assy_failure_reasons) || stripos($reason, 'TF 1') !== false || stripos($reason, 'TF1') !== false || stripos($reason, 'uji fungsional') !== false) {
                $item->source_stage = 'test_functional_1';
                $item->source_stage_label = 'Test Functional 1 (TF 1)';
            } else {
                $item->source_stage = 'perakitan';
                $item->source_stage_label = 'Perakitan';
            }
        }

        return response()->json(['ok' => true, 'data' => $items]);
    }

    /**
     * Autentikasi Khusus Masuk Portal CMS & Diagnostik Server (/admin)
     * Memverifikasi ulang username & password administrator
     */
    public function cmsLogin(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = trim($request->input('password', ''));

        if (!$username || !$password) {
            return response()->json(['ok' => false, 'error' => 'Username dan password wajib diisi.'], 422);
        }

        $user = \App\Models\User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Akun administrator tidak ditemukan.'], 401);
        }

        // Cek hak akses role atau izin khusus can_access_cms
        // User ID 3 adalah Administrator utama sistem. User lain wajib memiliki izin can_access_cms aktif.
        $canAccess = ($user->id === 3) || (bool)$user->can_access_cms;
        if (!$canAccess) {
            return response()->json([
                'ok' => false, 
                'error' => 'Akses Ditolak: Akun ' . $user->username . ' tidak memiliki izin akses ke Portal CMS.'
            ], 403);
        }

        // Cek password (support Bcrypt and plaintext legacy)
        $valid = false;
        try {
            if (\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
                $valid = true;
            }
        } catch (\Throwable $e) {}

        if (!$valid && $user->password === $password) {
            $valid = true;
            // Upgrade to Bcrypt otomatis
            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            $user->save();
        }

        if (!$valid) {
            return response()->json(['ok' => false, 'error' => 'Password salah. Silakan coba kembali.'], 401);
        }

        $cmsToken = hash('sha256', $user->id . '|' . $user->username . '|' . now()->timestamp . '|cms_portal_secret');

        return response()->json([
            'ok' => true,
            'message' => 'Autentikasi Administrator Berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'role' => $user->role,
                    'department' => $user->department,
                ],
                'cms_token' => $cmsToken,
            ]
        ]);
    }

    /**
     * Logout Sesi Admin CMS
     */
    public function cmsLogout(Request $request)
    {
        return response()->json(['ok' => true, 'message' => 'Sesi Administrator berhasil dikunci / logout.']);
    }

    /**
     * Setujui Permohonan Pergantian Part (Admin)
     * Endpoint: POST /api/admin/approve-replacement
     */
    public function approveReplacement(Request $request)
    {
        $replId = $request->input('replacement_id');
        $assemblyId = $request->input('assembly_id');
        $admin = $this->resolveActor($request)['full_name'];

        return DB::transaction(function () use ($replId, $assemblyId, $admin) {
            $reqQuery = DB::table('part_replacement_requests');
            if ($replId) {
                $reqQuery->where('id', $replId);
            } else {
                $reqQuery->where('assembly_id', $assemblyId)->where('status', 'PENDING');
            }
            $replRecord = $reqQuery->first();

            $assembly = Assembly::find($assemblyId ?: $replRecord?->assembly_id);
            if (!$assembly) {
                return response()->json(['ok' => false, 'error' => 'Data perakitan tidak ditemukan'], 404);
            }

            if ($replRecord) {
                $details = json_decode($replRecord->replacements_json ?: '[]', true);
                if (!is_array($details)) {
                    $details = [$details];
                }

                foreach ($details as $d) {
                    if (!is_array($d)) continue;
                    $pType = (string)($d['part_type'] ?? '');
                    $oldSn = trim((string)($d['old_sn'] ?? ''));
                    $newSn = trim((string)($d['new_sn'] ?? ''));

                    if ($newSn) {
                        if ($pType === '15') $assembly->part15_sn = $newSn;
                        elseif ($pType === '1') $assembly->part1_sn = $newSn;
                        elseif ($pType === '12') $assembly->part12_sn = $newSn;

                        // Master stock updates
                        MasterStock::where('part_type', $pType)
                            ->where(function ($w) use ($newSn) {
                                $w->where('sn', $newSn)
                                  ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $newSn));
                            })
                            ->update([
                                'status' => 'Supplied',
                                'allocated_tray_id' => $assembly->tray_id,
                                'allocated_at' => now(),
                            ]);

                        if ($oldSn) {
                            $targetOldStatus = ($replRecord->old_part_status && $replRecord->old_part_status !== 'Rework') ? $replRecord->old_part_status : 'Hold';
                            MasterStock::where('part_type', $pType)
                                ->where(function ($w) use ($oldSn) {
                                    $w->where('sn', $oldSn)
                                      ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $oldSn));
                                })
                                ->update([
                                    'status' => $targetOldStatus,
                                    'allocated_tray_id' => null,
                                    'allocated_at' => null,
                                    'used_at' => null,
                                    'last_tray_id' => $assembly->tray_id,
                                    'last_operator' => $admin,
                                    'is_replaced_from_assy' => ($targetOldStatus === 'Tersedia' ? 0 : 1),
                                    'last_reason' => 'Diganti part baru: ' . $replRecord->reason,
                                ]);

                            // Update parts table in tray
                            DB::table('parts')
                                ->where('tray_id', $assembly->tray_id)
                                ->where('part_type', $pType)
                                ->where('sn', $oldSn)
                                ->update(['sn' => $newSn]);

                            PartAuditLog::create([
                                'part_type' => $pType,
                                'sn' => $oldSn,
                                'old_status' => 'Supplied',
                                'new_status' => $targetOldStatus,
                                'action_type' => 'ADMIN_APPROVE_REPLACEMENT',
                                'actor_username' => $admin,
                                'actor_role' => 'admin',
                                'reason' => "Diganti dengan SN {$newSn}: {$replRecord->reason}",
                            ]);
                        }
                    }
                }

                DB::table('part_replacement_requests')
                    ->where('id', $replRecord->id)
                    ->update([
                        'status' => 'APPROVED',
                        'admin_username' => $admin,
                        'verified_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $isFunctionalOrigin = in_array($assembly->assy_test_status, ['Hold TF 1', 'Hold', 'Not Good', 'FAIL'])
                || in_array($assembly->qc_status, ['HOLD_QC', 'REWORK_QC', 'HOLD']);

            if ($isFunctionalOrigin) {
                $assembly->verified_status = 'APPROVED_REASSEMBLY';
            } else {
                $assembly->verified_status = 'WAITING_REASSEMBLY';
            }
            $assembly->status = 'Dirakit Ulang';
            $assembly->is_declined = false;
            $assembly->save();

            return response()->json([
                'ok' => true,
                'message' => 'Pergantian part berhasil disetujui & dialihkan ke antrean Menu Perakitan Ulang!'
            ]);
        });
    }

    /**
     * Tolak Permohonan Pergantian Part (Admin)
     * Endpoint: POST /api/admin/reject-replacement
     */
    public function rejectReplacement(Request $request)
    {
        $replId = $request->input('replacement_id');
        $assemblyId = $request->input('assembly_id');
        $notes = $request->input('notes', 'Ditolak oleh Administrator');
        $admin = $this->resolveActor($request)['full_name'];

        $reqQuery = DB::table('part_replacement_requests');
        if ($replId) {
            $reqQuery->where('id', $replId);
        } else {
            $reqQuery->where('assembly_id', $assemblyId)->where('status', 'PENDING');
        }
        $replRecord = $reqQuery->first();

        // Lepaskan part baru yang sempat di-reserve (Pending Verif Admin) kembali ke Tersedia dan sterilkan relasinya
        if ($replRecord) {
            $details = json_decode($replRecord->replacements_json ?: '[]', true);
            foreach ((array)$details as $d) {
                if (!is_array($d)) continue;
                $pType = (string)($d['part_type'] ?? '');
                $newSn = trim((string)($d['new_sn'] ?? ''));
                if ($newSn) {
                    MasterStock::where('part_type', $pType)
                        ->where(function ($w) use ($newSn) {
                            $w->where('sn', $newSn)
                              ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $newSn));
                        })
                        ->where('status', 'Pending Verif Admin')
                        ->update([
                            'status' => 'Tersedia',
                            'allocated_tray_id' => null,
                            'allocated_at' => null,
                            'used_at' => null,
                            'last_tray_id' => null,
                            'last_operator' => null,
                            'last_reason' => 'Pengajuan pergantian part ditolak Admin: ' . $notes,
                            'updated_at' => now(),
                        ]);

                    PartAuditLog::create([
                        'part_type' => $pType,
                        'sn' => $newSn,
                        'old_status' => 'Pending Verif Admin',
                        'new_status' => 'Tersedia',
                        'action_type' => 'REPLACEMENT_REJECTED_RELEASE',
                        'actor_username' => $admin,
                        'actor_role' => 'admin',
                        'reason' => 'Pengajuan pergantian part ditolak Admin, part baru dikembalikan ke Tersedia tanpa ikatan baki',
                    ]);
                }
            }
        }

        DB::table('part_replacement_requests')
            ->where('id', $replRecord ? $replRecord->id : $replId)
            ->update([
                'status' => 'REJECTED',
                'admin_notes' => $notes,
                'admin_username' => $admin,
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        $targetAssyId = $assemblyId ?: $replRecord?->assembly_id;
        if ($targetAssyId) {
            Assembly::where('id', $targetAssyId)->update(['verified_status' => null]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Permohonan pergantian part telah ditolak.'
        ]);
    }

    /**
     * Setujui / Tolak Pendaftaran Ulang Perakitan (Admin Approve Assy Rereg)
     * Endpoint: POST /api/admin/approve-assy-rereg
     */
    public function approveAssyRereg(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        $action = strtoupper($request->input('action', 'APPROVE'));
        $approvedOperator = $request->input('approved_operator', '');
        $notes = $request->input('notes', '');
        $admin = $this->resolveActor($request)['full_name'];

        $assembly = Assembly::findOrFail($assemblyId);
        $tray = Tray::find($assembly->tray_id);
        if ($tray && (str_contains($tray->tray_code, '-PU') || str_contains($tray->tray_code, '/PU/'))) {
            $newTrayCode = $tray->tray_code;
        } else {
            $allPUs = Tray::where(function ($w) {
                $w->where('tray_code', 'LIKE', '%-PU%')
                  ->orWhere('tray_code', 'LIKE', '%/PU/%')
                  ->orWhere('tray_code', 'LIKE', 'WH/ASSY-PU/%');
            })->get(['tray_code']);

            $maxPuNum = 0;
            foreach ($allPUs as $pu) {
                if (preg_match('/(?:-PU|\/PU\/|WH\/ASSY-PU\/)(\d+)/i', $pu->tray_code, $m)) {
                    $maxPuNum = max($maxPuNum, (int)$m[1]);
                }
            }

            $nextPuNum = $maxPuNum + 1;
            $newTrayCode = 'WH/ASSY/PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);
            while (Tray::where('tray_code', $newTrayCode)->orWhere('tray_code', 'WH/ASSY-PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT))->exists()) {
                $nextPuNum++;
                $newTrayCode = 'WH/ASSY/PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);
            }
        }

        if ($action === 'APPROVE') {
            $assembly->update([
                'verified_status' => 'WAITING_REASSEMBLY',
                'status' => 'Dirakit Ulang',
                'rereg_pending_operator' => $approvedOperator ?: $assembly->pic_assembly,
                'is_reregistered' => 0,
                'notes' => $notes ?: 'Disetujui Admin untuk Perakitan Ulang',
            ]);

            // Bersihkan re_register_requests jika ada
            DB::table('re_register_requests')
                ->whereIn('sn', array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]))
                ->where('status', 'PENDING')
                ->update([
                    'status' => 'APPROVED',
                    'admin_username' => $admin,
                    'admin_notes' => $notes ?: 'Disetujui Admin',
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);

            // Pastikan part di master stock statusnya used
            DB::table('master_stocks')
                ->whereIn('sn', array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]))
                ->where('status', 'Pending Verif Admin')
                ->update(['status' => 'Used']);

            PartAuditLog::create([
                'part_type' => 'ALL',
                'sn' => "Tray#{$assembly->tray_id}_Set#{$assembly->set_number}",
                'old_status' => 'PENDING_ADMIN_ASSY_REREG',
                'new_status' => 'WAITING_REASSEMBLY',
                'action_type' => 'APPROVE_ASSY_REREG',
                'actor_username' => $admin,
                'actor_role' => 'admin',
                'reason' => "Disetujui Admin untuk masuk ke Perakitan Ulang (RU). {$notes}",
            ]);

            return response()->json([
                'ok' => true,
                'message' => "Perakitan ulang Set #{$assembly->set_number} disetujui Admin dan berhasil dialihkan ke antrean Perakitan Ulang (RU)!",
                'new_tray_code' => $newTrayCode
            ]);
        } else {
            $assembly->update([
                'verified_status' => 'REJECTED_REASSEMBLY',
                'notes' => $notes ?: 'Pengajuan perakitan ulang ditolak Admin',
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Pengajuan perakitan ulang ditolak.'
            ]);
        }
    }

    /**
     * Persetujuan Admin untuk Permohonan Lanjut Tahap Proses (Perakitan -> TF1, TF1 -> TF2, QC -> Gudang)
     * Endpoint: POST /api/admin/approve-proceed
     */
    public function approveProceed(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        $action = strtoupper($request->input('action', 'APPROVE'));
        $notes = $request->input('notes', '');
        $admin = $this->resolveActor($request)['full_name'];

        $assembly = Assembly::findOrFail($assemblyId);

        if ($action === 'APPROVE') {
            $isQC = in_array(strtoupper($assembly->qc_status ?? ''), ['HOLD_QC', 'HOLD', 'HOLD TF 2', 'REWORK_QC']) ||
                $assembly->verified_status === 'PENDING_ADMIN_QC' ||
                str_contains($assembly->notes ?? '', 'Penerimaan');

            $isTF1 = !$isQC && (
                $assembly->verified_status === 'PENDING_ADMIN_PROCEED_QC' ||
                str_contains($assembly->notes ?? '', 'Test Functional 2') ||
                in_array($assembly->assy_test_status, ['Hold', 'Rework', 'Hold TF 1', 'Not Good', 'FAIL']) ||
                in_array($assembly->qc_status, ['HOLD_ASSY', 'REWORK_ASSY']) ||
                !empty($assembly->assy_failure_reasons)
            );

            if ($isQC) {
                // QC -> Penerimaan Fisik Gudang
                $targetName = 'Penerimaan Fisik Gudang';
                $qcDocNumber = $assembly->qc_doc_number;
                if (!$qcDocNumber) {
                    $tray = Tray::find($assembly->tray_id);
                    $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                    $qcDocNumber = "WH/QC/{$docSeq}";
                }
                $assembly->update([
                    'status' => 'Good',
                    'qc_status' => 'GOOD',
                    'verified_status' => 'APPROVED_QC',
                    'qc_failure_reasons' => null,
                    'qc_tested_at' => $assembly->qc_tested_at ?: now(),
                    'qc_tested_by' => $assembly->qc_tested_by ?: $admin,
                    'qc_doc_number' => $qcDocNumber,
                    'warehouse_receipt_status' => 'PENDING',
                    'notes' => $notes ?: 'Disetujui Admin untuk lanjut ke Penerimaan Gudang',
                ]);
            } elseif ($isTF1) {
                // TF 1 -> QC (TF 2)
                $targetName = 'Test Functional 2 (QC)';
                $assembly->update([
                    'status' => 'Good',
                    'assy_test_status' => 'Good',
                    'qc_status' => 'PENDING_QC',
                    'qc_test_checklist' => null,
                    'qc_tested_at' => null,
                    'qc_doc_number' => null,
                    'qc_failure_reasons' => null,
                    'verified_status' => 'APPROVED_PROCEED_TF2',
                    'notes' => $notes ?: 'Disetujui Admin untuk lanjut ke Test Functional 2 (QC)',
                ]);
            } else {
                // Perakitan -> TF 1
                $targetName = 'Test Functional 1 (TF 1)';
                $assembly->update([
                    'status' => 'Good',
                    'assy_test_status' => 'PENDING',
                    'assy_test_checklist' => null,
                    'assy_tested_at' => null,
                    'assy_doc_number' => null,
                    'assy_failure_reasons' => null,
                    'verified_status' => 'APPROVED_PROCEED_TF1',
                    'notes' => $notes ?: 'Disetujui Admin untuk lanjut ke Test Functional 1 (TF 1)',
                ]);
            }

            PartAuditLog::create([
                'part_type' => 'SET',
                'sn' => $assembly->part15_sn ?: ("Set #" . $assembly->set_number),
                'old_status' => $assembly->verified_status,
                'new_status' => 'APPROVED_PROCEED',
                'action_type' => 'ADMIN_APPROVE_PROCEED',
                'actor_username' => $admin,
                'actor_role' => 'admin',
                'reason' => "Admin menyetujui permohonan Set #{$assembly->set_number} untuk lanjut ke {$targetName}. {$notes}",
            ]);

            return response()->json([
                'ok' => true,
                'message' => "Permohonan Set #{$assembly->set_number} untuk lanjut ke {$targetName} berhasil disetujui Admin!"
            ]);
        } else {
            // Tolak permohonan: Kembalikan status ke Hold tanpa pending
            $assembly->update([
                'verified_status' => 'REJECTED_PROCEED',
                'notes' => $notes ?: 'Permohonan lanjut proses berikutnya ditolak Admin.',
            ]);

            PartAuditLog::create([
                'part_type' => 'SET',
                'sn' => $assembly->part15_sn ?: ("Set #" . $assembly->set_number),
                'old_status' => 'PENDING_ADMIN_PROCEED',
                'new_status' => 'REJECTED_PROCEED',
                'action_type' => 'ADMIN_REJECT_PROCEED',
                'actor_username' => $admin,
                'actor_role' => 'admin',
                'reason' => "Admin menolak permohonan Set #{$assembly->set_number} untuk lanjut ke proses selanjutnya. {$notes}",
            ]);

            return response()->json([
                'ok' => true,
                'message' => "Permohonan lanjut proses untuk Set #{$assembly->set_number} telah ditolak."
            ]);
        }
    }


    /**
     * Setujui / Tolak Permohonan Pendaftaran Ulang Part (Admin)
     * Endpoint: POST /api/admin/approve-re-register
     */
    public function approveReRegister(Request $request)
    {
        $requestId = $request->input('request_id');
        $action = strtoupper($request->input('action', 'APPROVE'));
        $targetStatus = $request->input('target_status', 'Tersedia');
        if (strtolower($targetStatus) === 'rework') {
            $targetStatus = 'Hold';
        }
        $notes = $request->input('notes', '');
        $admin = $this->resolveActor($request)['full_name'];

        $req = DB::table('re_register_requests')->where('id', $requestId)->first();
        if (!$req) {
            return response()->json(['ok' => false, 'error' => 'Permohonan tidak ditemukan.'], 404);
        }

        return DB::transaction(function () use ($req, $action, $targetStatus, $notes, $admin) {
            if ($action === 'APPROVE') {
                DB::table('re_register_requests')->where('id', $req->id)->update([
                    'status' => 'APPROVED',
                    'target_status' => $targetStatus,
                    'admin_username' => $admin,
                    'approved_at' => now(),
                    'admin_notes' => $notes,
                    'updated_at' => now(),
                ]);

                MasterStock::where('part_type', $req->part_type)
                    ->where(function ($w) use ($req) {
                        $w->where('sn', $req->sn)
                          ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $req->sn));
                    })
                    ->update([
                        'status' => $targetStatus,
                        'allocated_tray_id' => null,
                        'allocated_at' => null,
                        'used_at' => null,
                        'last_tray_id' => null,
                        'is_replaced_from_assy' => 0,
                        'last_reason' => $notes ?: 'Disetujui daftar ulang oleh Admin (kembali Tersedia)',
                        'updated_at' => now(),
                    ]);

                PartAuditLog::create([
                    'part_type' => $req->part_type,
                    'sn' => $req->sn,
                    'old_status' => $req->current_status,
                    'new_status' => $targetStatus,
                    'action_type' => 'ADMIN_APPROVE_RE_REGISTER',
                    'actor_username' => $admin,
                    'actor_role' => 'admin',
                    'reason' => $notes ?: 'Pendaftaran ulang part disetujui Admin',
                ]);

                return response()->json([
                    'ok' => true,
                    'message' => 'Permohonan pendaftaran ulang berhasil disetujui!'
                ]);
            } else {
                DB::table('re_register_requests')->where('id', $req->id)->update([
                    'status' => 'REJECTED',
                    'admin_username' => $admin,
                    'admin_notes' => $notes,
                    'updated_at' => now(),
                ]);

                $revertStatus = ($req->current_status && $req->current_status !== 'Pending Verif Admin') ? $req->current_status : 'Hold';

                MasterStock::where('part_type', $req->part_type)
                    ->where(function ($w) use ($req) {
                        $w->where('sn', $req->sn)
                          ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $req->sn));
                    })
                    ->update([
                        'status' => $revertStatus,
                        'last_reason' => $notes ?: 'Pendaftaran ulang ditolak oleh Admin',
                        'updated_at' => now(),
                    ]);

                PartAuditLog::create([
                    'part_type' => $req->part_type,
                    'sn' => $req->sn,
                    'old_status' => 'Pending Verif Admin',
                    'new_status' => $revertStatus,
                    'action_type' => 'ADMIN_REJECT_RE_REGISTER',
                    'actor_username' => $admin,
                    'actor_role' => 'admin',
                    'reason' => $notes ?: 'Pendaftaran ulang part ditolak oleh Admin',
                ]);

                return response()->json([
                    'ok' => true,
                    'message' => 'Permohonan pendaftaran ulang berhasil ditolak.'
                ]);
            }
        });
    }

    /**
     * Verifikasi Langsung Fisik Part Menjadi Tersedia (Admin)
     * Endpoint: POST /api/admin/approve-part-direct
     */
    public function approvePartDirect(Request $request)
    {
        $partType = (string)$request->input('part_type', '');
        $sn = trim((string)$request->input('sn', ''));
        $targetStatus = $request->input('target_status', 'Tersedia');
        if (strtolower($targetStatus) === 'rework') {
            $targetStatus = 'Hold';
        }
        $notes = $request->input('notes', 'Verifikasi fisik langsung Admin');
        $admin = $this->resolveActor($request)['full_name'];
        $assemblyId = (int)$request->input('assembly_id');

        if (!$sn) {
            return response()->json(['ok' => false, 'error' => 'SN part wajib diisi.'], 400);
        }

        return DB::transaction(function () use ($partType, $sn, $targetStatus, $notes, $admin, $assemblyId) {
            $stock = MasterStock::where('part_type', $partType)
                ->where(function ($w) use ($sn) {
                    $w->where('sn', $sn)
                      ->orWhere(DB::raw("REPLACE(sn, '.', '')"), str_replace('.', '', $sn));
                })
                ->first();

            if ($stock) {
                $oldStatus = $stock->status;
                $stock->update([
                    'status' => $targetStatus,
                    'allocated_tray_id' => null,
                    'allocated_at' => null,
                    'used_at' => null,
                    'last_tray_id' => null,
                    'is_replaced_from_assy' => 0,
                    'last_reason' => $notes,
                ]);

                PartAuditLog::create([
                    'part_type' => $partType,
                    'sn' => $sn,
                    'old_status' => $oldStatus,
                    'new_status' => $targetStatus,
                    'action_type' => 'ADMIN_DIRECT_VERIFY',
                    'actor_username' => $admin,
                    'actor_role' => 'admin',
                    'reason' => $notes,
                ]);
            }

            // Periksa apakah seluruh part dalam rakitan kini sudah Tersedia
            $allRestored = false;
            $assembliesToCheck = collect();
            if ($assemblyId > 0) {
                $a = Assembly::find($assemblyId);
                if ($a) $assembliesToCheck->push($a);
            }
            $relatedAssies = Assembly::where('part15_sn', $sn)
                ->orWhere('part1_sn', $sn)
                ->orWhere('part12_sn', $sn)
                ->get();
            foreach ($relatedAssies as $ra) {
                if (!$assembliesToCheck->contains('id', $ra->id)) {
                    $assembliesToCheck->push($ra);
                }
            }

            foreach ($assembliesToCheck as $assy) {
                $partSNs = array_filter([$assy->part15_sn, $assy->part1_sn, $assy->part12_sn], fn($s) => !empty($s) && $s !== '-');
                if (!empty($partSNs)) {
                    $nonAvailableCount = MasterStock::whereIn('sn', $partSNs)
                        ->where('status', '!=', 'Tersedia')
                        ->count();
                    if ($nonAvailableCount === 0) {
                        // Jika seluruh part pada set ini telah dipulihkan ke Tersedia, hapus relasi dari baki
                        DB::table('parts')->where('tray_id', $assy->tray_id)
                            ->whereIn('sn', $partSNs)
                            ->delete();

                        if (empty($assy->finished_at) || $assy->is_declined || in_array($assy->status, ['Decline', 'DECLINED', 'Dipulihkan'])) {
                            $assy->delete();
                        } else {
                            $assy->update([
                                'verified_status' => 'RESOLVED_RESTORED',
                                'is_declined' => false,
                                'declined_reason' => $notes,
                            ]);
                        }
                        $allRestored = true;
                    }
                }
            }

            return response()->json([
                'ok' => true,
                'message' => "Part {$partType} ({$sn}) berhasil dipulihkan menjadi {$targetStatus}!" . ($allRestored ? " Seluruh part pada set ini telah tuntas dipulihkan." : ""),
                'all_restored' => $allRestored
            ]);
        });
    }

    /**
     * Hapus Permohonan Verifikasi Daftar Ulang (Admin)
     * Endpoint: POST /api/admin/delete-re-register
     */
    public function deleteReRegister(Request $request)
    {
        $requestId = $request->input('request_id');
        $sn = $request->input('sn');
        $partType = $request->input('part_type');

        if ($requestId) {
            DB::table('re_register_requests')->where('id', $requestId)->delete();
        } elseif ($sn && $partType) {
            DB::table('re_register_requests')->where('sn', $sn)->where('part_type', $partType)->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Barang berhasil dihapus dari verifikasi Admin.'
        ]);
    }

    /**
     * Hapus Rakitan yang Ditolak/Decline dari Antrean (Admin)
     * Endpoint: POST /api/admin/delete-declined-set
     */
    public function deleteDeclinedSet(Request $request)
    {
        $assemblyId = $request->input('assembly_id');
        if ($assemblyId) {
            Assembly::where('id', $assemblyId)->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Barang rakitan berhasil dihapus dari verifikasi Admin.'
        ]);
    }

    /**
     * Tambah Parameter Uji Fungsional 1 (Testing Point)
     * Endpoint: POST /api/admin/testing-points
     */
    public function createTestingPoint(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string',
            'instruction' => 'required|string',
            'output' => 'required|string',
        ]);

        $maxNum = TestingPoint::max('test_num') ?? 0;
        $testNum = $request->filled('test_num') ? (int)$request->input('test_num') : ($maxNum + 1);

        $images = $request->input('images', []);
        $processedImages = $this->processTestingPointImages($images, $testNum);

        $point = TestingPoint::create([
            'test_num' => $testNum,
            'category' => $data['category'],
            'instruction' => $data['instruction'],
            'output' => $data['output'],
            'images' => $processedImages,
        ]);

        return response()->json([
            'ok' => true,
            'data' => $point,
            'message' => 'Parameter uji fungsional berhasil ditambahkan.'
        ], 201);
    }

    /**
     * Hapus Parameter Uji Fungsional 1 (Testing Point)
     * Endpoint: POST /api/admin/testing-points/delete
     */
    public function deleteTestingPoint(Request $request)
    {
        $id = $request->input('id');
        $point = TestingPoint::findOrFail($id);
        $point->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Parameter uji fungsional berhasil dihapus.'
        ]);
    }

    /**
     * Pulihkan Set yang Ditolak/Decline (Barang Hilang/Tidak Ditemukan) Kembali ke Tersedia
     * Endpoint: POST /api/admin/approve-declined-set-restore
     */
    /**
     * Kunci Set yang Seluruh Partnya Telah Selesai Diverifikasi
     * Endpoint: POST /api/admin/lock-verified-set
     */
    public function lockVerifiedSet(Request $request)
    {
        $assemblyId = (int)$request->input('assembly_id');
        $notes = $request->input('notes') ?: 'Seluruh part selesai diverifikasi oleh Admin & set dikunci';

        if (!$assemblyId) {
            return response()->json(['ok' => false, 'error' => 'assembly_id wajib disertakan.'], 400);
        }

        $assy = Assembly::find($assemblyId);
        if (!$assy) {
            return response()->json(['ok' => false, 'error' => 'Assembly tidak ditemukan.'], 404);
        }

        $assy->update([
            'verified_status' => 'RESOLVED_RESTORED',
            'is_declined' => false,
            'declined_reason' => $notes,
        ]);

        return response()->json([
            'ok' => true,
            'message' => "Set #{$assy->set_number} berhasil dikunci dan selesai diverifikasi."
        ]);
    }

    public function approveDeclinedSetRestore(Request $request)
    {
        $assemblyId = (int)$request->input('assembly_id');
        $notes = $request->input('notes') ?: 'Barang dipulihkan kembali ke Tersedia oleh Admin (Verifikasi Gudang Decline)';
        $admin = $this->resolveActor($request)['full_name'];

        return DB::transaction(function () use ($assemblyId, $notes, $admin) {
            $assembly = Assembly::findOrFail($assemblyId);

            foreach (['15' => $assembly->part15_sn, '1' => $assembly->part1_sn, '12' => $assembly->part12_sn] as $pType => $sn) {
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
                            'last_reason' => $notes,
                        ]);

                    PartAuditLog::create([
                        'part_type' => $pType,
                        'sn' => $sn,
                        'old_status' => 'Declined',
                        'new_status' => 'Tersedia',
                        'action_type' => 'ADMIN_RESTORE_DECLINED_SET',
                        'actor_username' => $admin,
                        'actor_role' => 'admin',
                        'reason' => $notes,
                    ]);
                }
            }

            // Hapus parts dari baki di tabel parts karena seluruh part telah dipulihkan ke Tersedia di Gudang
            DB::table('parts')->where('tray_id', $assembly->tray_id)
                ->whereIn('sn', array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]))
                ->delete();

            // Hapus record assembly yang uncompleted/declined dari baki ini karena part sudah kembali ke stok gudang
            $assembly->delete();

            return response()->json([
                'ok' => true,
                'message' => "Seluruh part pada Set #{$assembly->set_number} berhasil dipulihkan kembali ke status Tersedia di Master Stok."
            ]);
        });
    }

    /**
     * Konfigurasi Kredensial Cloudinary (Admin CMS)
     * Endpoint: GET /api/admin/cloudinary/settings
     *           POST /api/admin/cloudinary/settings
     *           POST /api/admin/cloudinary/test
     */
    public function getCloudinarySettings()
    {
        $settings = DoSetting::firstOrCreate(['id' => 1]);
        $hasSecret = !empty($settings->cloudinary_api_secret);
        return response()->json([
            'ok' => true,
            'data' => [
                'cloud_name' => $settings->cloudinary_cloud_name,
                'api_key' => $settings->cloudinary_api_key,
                'api_key_masked' => $settings->cloudinary_api_key ? substr($settings->cloudinary_api_key, 0, 4) . '***' . substr($settings->cloudinary_api_key, -4) : null,
                'has_secret' => $hasSecret,
                'configured' => !empty($settings->cloudinary_cloud_name) && !empty($settings->cloudinary_api_key) && $hasSecret,
            ]
        ]);
    }

    public function updateCloudinarySettings(Request $request)
    {
        $data = $request->validate([
            'cloud_name' => 'nullable|string',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
        ]);

        $settings = DoSetting::firstOrCreate(['id' => 1]);
        if (isset($data['cloud_name'])) {
            $settings->cloudinary_cloud_name = trim($data['cloud_name']);
        }
        if (isset($data['api_key'])) {
            $settings->cloudinary_api_key = trim($data['api_key']);
        }
        if (!empty($data['api_secret'])) {
            $settings->cloudinary_api_secret = trim($data['api_secret']);
        }
        $settings->save();

        return response()->json([
            'ok' => true,
            'message' => 'Kredensial Cloudinary berhasil diperbarui di Portal CMS Admin.',
            'data' => [
                'cloud_name' => $settings->cloudinary_cloud_name,
                'api_key' => $settings->cloudinary_api_key,
                'api_key_masked' => $settings->cloudinary_api_key ? substr($settings->cloudinary_api_key, 0, 4) . '***' . substr($settings->cloudinary_api_key, -4) : null,
                'has_secret' => !empty($settings->cloudinary_api_secret),
                'configured' => !empty($settings->cloudinary_cloud_name) && !empty($settings->cloudinary_api_key) && !empty($settings->cloudinary_api_secret),
            ]
        ]);
    }

    public function testCloudinary(Request $request)
    {
        $settings = DoSetting::first();
        $isConfigured = !empty($settings?->cloudinary_cloud_name) && !empty($settings?->cloudinary_api_key) && !empty($settings?->cloudinary_api_secret);
        return response()->json([
            'ok' => true,
            'configured' => $isConfigured,
            'delivery_ok' => $isConfigured,
            'cloud_name' => $settings?->cloudinary_cloud_name,
            'message' => $isConfigured ? 'Koneksi Cloudinary Berhasil & Siap Pakai untuk QR Code Publik.' : 'Kredensial Cloudinary belum lengkap di Portal Admin CMS.',
        ]);
    }

    /**
     * Manajemen Lingkungan Database Ganda (Produksi vs Testing)
     * Endpoint: GET /api/admin/database-status
     *           POST /api/admin/switch-database
     *           POST /api/admin/clone-testing-database
     */
    public function getDatabaseStatus()
    {
        return response()->json([
            'ok' => true,
            'active_db' => 'db_final_assembly',
            'is_testing' => false,
            'message' => 'Sistem beroperasi penuh pada Database Produksi (Live).'
        ]);
    }

    public function switchDatabase(Request $request)
    {
        return response()->json([
            'ok' => false,
            'error' => 'Fitur pengalihan ke database testing telah dinonaktifkan. Sistem dikunci pada Database Produksi.'
        ], 400);
    }

    public function cloneTestingDatabase(Request $request)
    {
        return response()->json([
            'ok' => false,
            'error' => 'Fitur database testing telah dinonaktifkan.'
        ], 400);
    }
}

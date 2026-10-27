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
        $admin = auth()->user()?->username ?: 'admin';
        $res = $this->maintenanceService->clearTraysAndRestoreStock($admin);

        if (!$res['ok']) {
            return $this->errorResponse($res['message'], 500);
        }

        return $this->successResponse($res['data'], $res['message']);
    }

    /**
     * Fitur 2 CMS: Hapus Riwayat Jeda / Downtime Operator
     * Endpoint: POST /api/admin/cms/clear-downtimes
     */
    public function clearDowntimes(Request $request)
    {
        $admin = auth()->user()?->username ?: 'admin';
        $res = $this->maintenanceService->clearDowntimeLogs($admin);

        if (!$res['ok']) {
            return $this->errorResponse($res['message'], 500);
        }

        return $this->successResponse($res['data'], $res['message']);
    }

    /**
     * Fitur 3 CMS: Hapus Seluruh Audit Trail & Log Aktivitas Sistem
     * Endpoint: POST /api/admin/cms/clear-audit-logs
     */
    public function clearAuditLogs(Request $request)
    {
        $admin = auth()->user()?->username ?: 'admin';
        $res = $this->maintenanceService->clearAuditLogs($admin);

        if (!$res['ok']) {
            return $this->errorResponse($res['message'], 500);
        }

        return $this->successResponse($res['data'], $res['message']);
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
        $data = $request->validate([
            'default_receiver_name' => 'nullable|string',
            'default_receiver_title' => 'nullable|string',
            'default_receiver_department' => 'nullable|string',
            'default_receiver_company' => 'nullable|string',
            'default_receiver_address' => 'nullable|string',
            'default_receiver_city' => 'nullable|string',
            'default_courier_name' => 'nullable|string',
            'default_license_plate' => 'nullable|string',
            'default_shipper_company' => 'nullable|string',
            'default_shipper_address' => 'nullable|string',
            'default_signer_name' => 'nullable|string',
            'default_signer_title' => 'nullable|string',
            'default_approved_by_name' => 'nullable|string',
            'default_approved_by_title' => 'nullable|string',
            'default_known_by_name' => 'nullable|string',
            'default_known_by_title' => 'nullable|string',
            'default_product_name' => 'nullable|string',
            'default_product_code' => 'nullable|string',
            'do_number_prefix' => 'nullable|string',
            'pl_number_prefix' => 'nullable|string',
            'tracking_base_url' => 'nullable|string',
            'do_sequence_offset' => 'nullable|integer',
            'do_offset_month' => 'nullable|string',
        ]);

        $settings = DoSetting::firstOrCreate(['id' => 1]);
        $settings->update($data);

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

        $point = TestingPoint::findOrFail($request->id);
        $point->update([
            'category' => $request->input('category', $point->category),
            'instruction' => $request->input('instruction', $point->instruction),
            'output' => $request->input('output', $point->output),
        ]);

        return $this->successResponse($point, 'Parameter uji fungsional 1 berhasil disimpan.');
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
        $logs = $query->orderBy('id', 'desc')->limit(500)->get();
        return response()->json(['ok' => true, 'data' => $logs]);
    }

    public function getPendingVerifications(Request $request)
    {
        $items = DB::table('re_register_requests as r')
            ->leftJoin('master_stocks as m', function ($join) {
                $join->on('r.part_type', '=', 'm.part_type')
                     ->on('r.sn', '=', 'm.sn');
            })
            ->select([
                'r.*',
                'm.cs',
                'm.last_reason as stock_last_reason',
                'm.is_replaced_from_assy'
            ])
            ->where('r.status', 'PENDING')
            ->orderBy('r.id', 'desc')
            ->get();

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function getPendingAssemblies(Request $request)
    {
        $items = DB::table('assemblies as a')
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
                'a.*',
                'a.id as assembly_id',
                't.tray_code',
                't.source_document_mo',
                't.product_type',
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly"),
                DB::raw("COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse"),
                't.created_at as tray_created_at'
            ])
            ->where(function ($w) {
                $w->where('a.verified_status', 'PENDING_ADMIN_ASSY_REREG')
                  ->orWhere('a.status', 'Hold')
                  ->orWhere('a.status', 'Rework')
                  ->orWhere('a.status', 'Scrap')
                  ->orWhere('a.qc_status', 'HOLD_QC');
            })
            ->orderBy('a.id', 'desc')
            ->get();

        return response()->json(['ok' => true, 'data' => $items]);
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
                'a.status as assembly_status', 'a.qc_status', 'a.verified_status',
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly"),
                't.tray_code', 't.source_document_mo', 't.product_type',
                't.created_at as tray_created_at'
            ])
            ->orderByRaw("CASE WHEN prr.status = 'PENDING' THEN 0 ELSE 1 END, prr.id DESC")
            ->get();

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
        $admin = auth()->user()?->username ?: 'admin';

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
                            $targetOldStatus = $replRecord->old_part_status ?: 'Scrap';
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
                                    'last_tray_id' => null,
                                    'is_replaced_from_assy' => 1,
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

            $assembly->verified_status = 'WAITING_REASSEMBLY';
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
        $admin = auth()->user()?->username ?: 'admin';

        $reqQuery = DB::table('part_replacement_requests');
        if ($replId) {
            $reqQuery->where('id', $replId);
        } else {
            $reqQuery->where('assembly_id', $assemblyId)->where('status', 'PENDING');
        }
        $reqQuery->update([
            'status' => 'REJECTED',
            'admin_notes' => $notes,
            'admin_username' => $admin,
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        if ($assemblyId) {
            Assembly::where('id', $assemblyId)->update(['verified_status' => null]);
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
        $admin = auth()->user()?->username ?: 'admin';

        $assembly = Assembly::findOrFail($assemblyId);
        $tray = Tray::find($assembly->tray_id);
        if ($tray && (str_contains($tray->tray_code, '-PU') || str_contains($tray->tray_code, '/PU/'))) {
            $newTrayCode = $tray->tray_code;
        } else {
            $lastPu = Tray::where('tray_code', 'LIKE', 'WH/ASSY-PU/%')->orderBy('id', 'desc')->first();
            $nextPuNum = 1;
            if ($lastPu && preg_match('/(\d+)$/', $lastPu->tray_code, $m)) {
                $nextPuNum = (int)$m[1] + 1;
            }
            $newTrayCode = 'WH/ASSY-PU/' . str_pad($nextPuNum, 6, '0', STR_PAD_LEFT);
        }

        if ($action === 'APPROVE') {
            $assembly->update([
                'verified_status' => 'WAITING_REASSEMBLY',
                'status' => 'Dirakit Ulang',
                'rereg_pending_operator' => $approvedOperator ?: $assembly->pic_assembly,
                'is_reregistered' => 1,
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
     * Setujui / Tolak Permohonan Pendaftaran Ulang Part (Admin)
     * Endpoint: POST /api/admin/approve-re-register
     */
    public function approveReRegister(Request $request)
    {
        $requestId = $request->input('request_id');
        $action = strtoupper($request->input('action', 'APPROVE'));
        $targetStatus = $request->input('target_status', 'Tersedia');
        $notes = $request->input('notes', '');
        $admin = auth()->user()?->username ?: 'admin';

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
        $notes = $request->input('notes', 'Verifikasi fisik langsung Admin');
        $admin = auth()->user()?->username ?: 'admin';

        if (!$sn) {
            return response()->json(['ok' => false, 'error' => 'SN part wajib diisi.'], 400);
        }

        return DB::transaction(function () use ($partType, $sn, $targetStatus, $notes, $admin) {
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

            return response()->json([
                'ok' => true,
                'message' => "Part {$partType} ({$sn}) berhasil dipulihkan menjadi {$targetStatus}!"
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
        $point = TestingPoint::create([
            'test_num' => $maxNum + 1,
            'category' => $data['category'],
            'instruction' => $data['instruction'],
            'output' => $data['output'],
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
    public function approveDeclinedSetRestore(Request $request)
    {
        $assemblyId = (int)$request->input('assembly_id');
        $notes = $request->input('notes') ?: 'Barang dipulihkan kembali ke Tersedia oleh Admin (Verifikasi Gudang Decline)';
        $admin = auth()->user()?->username ?: 'admin';

        return DB::transaction(function () use ($assemblyId, $notes, $admin) {
            $assembly = Assembly::findOrFail($assemblyId);

            foreach (['15' => $assembly->part15_sn, '1' => $assembly->part1_sn, '12' => $assembly->part12_sn] as $pType => $sn) {
                if ($sn && $sn !== '-') {
                    MasterStock::where('part_type', $pType)
                        ->where('sn', $sn)
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

            $assembly->update([
                'verified_status' => 'RESOLVED_RESTORED',
                'is_declined' => false,
                'declined_reason' => $notes,
            ]);

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


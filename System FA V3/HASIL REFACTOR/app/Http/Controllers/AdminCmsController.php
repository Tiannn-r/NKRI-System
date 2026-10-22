<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DatabaseMaintenanceService;
use App\Models\DoSetting;
use App\Models\Customer;
use App\Models\TestingPoint;

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
        $points = TestingPoint::orderBy('sort_order', 'asc')->get();
        return $this->successResponse($points);
    }

    public function updateTestingPoint(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',
            'standard' => 'nullable|string',
        ]);

        $point = TestingPoint::findOrFail($request->id);
        $point->update([
            'name' => $request->name,
            'standard' => $request->input('standard', ''),
        ]);

        return $this->successResponse($point, 'Parameter uji fungsional 1 berhasil disimpan.');
    }
}

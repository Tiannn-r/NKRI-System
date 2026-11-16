<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemErrorLog;
use App\Models\SlowQueryLog;
use App\Services\AnomalyDetectionService;

class DiagnosticController extends Controller
{
    protected AnomalyDetectionService $anomalyService;

    public function __construct(AnomalyDetectionService $anomalyService)
    {
        $this->anomalyService = $anomalyService;
    }

    /**
     * 1. Daftar Error Backend
     * Endpoint: GET /api/admin/error-logs
     */
    public function getErrorLogs(Request $request)
    {
        $limit = min((int) $request->query('limit', 100), 500);
        $logs = SystemErrorLog::orderBy('id', 'desc')->limit($limit)->get();
        return $this->successResponse($logs);
    }

    /**
     * Bersihkan Log Error Backend
     * Endpoint: POST /api/admin/error-logs/clear
     */
    public function clearErrorLogs()
    {
        SystemErrorLog::truncate();
        return $this->successResponse(null, 'Seluruh riwayat error log berhasil dibersihkan.');
    }

    /**
     * 2. Slow Query & Lag Monitor (>500ms)
     * Endpoint: GET /api/admin/slow-queries
     */
    public function getSlowQueries(Request $request)
    {
        $limit = min((int) $request->query('limit', 100), 500);
        $queries = SlowQueryLog::orderBy('id', 'desc')->limit($limit)->get();
        return $this->successResponse($queries);
    }

    /**
     * Bersihkan Log Slow Query
     * Endpoint: POST /api/admin/slow-queries/clear
     */
    public function clearSlowQueries()
    {
        SlowQueryLog::truncate();
        return $this->successResponse(null, 'Seluruh riwayat slow query log berhasil dibersihkan.');
    }

    /**
     * 3. Pemindai Anomali Sistem & Data Desync
     * Endpoint: GET /api/admin/anomalies/scan
     */
    public function scanAnomalies()
    {
        $results = $this->anomalyService->scanAll();
        return $this->successResponse($results);
    }

    /**
     * 4. 1-Click Auto Repair untuk Anomali
     * Endpoint: POST /api/admin/anomalies/repair
     */
    public function autoRepairAnomalies()
    {
        $admin = auth()->user()?->username ?: 'admin';
        $repairResults = $this->anomalyService->autoRepair($admin);
        return $this->successResponse($repairResults, '1-Click Auto Repair selesai dijalankan.');
    }

    /**
     * 5. Ultra-fast Badge Counts untuk Sidebar Navigation & Polling
     * Menggantikan beban download ratusan MB menjadi query COUNT(*) secepat kilat (0.2 KB)
     * Endpoint: GET /api/badge-counts
     */
    public function getBadgeCounts()
    {
        // 1. QC Pending count
        $qcPending = \Illuminate\Support\Facades\DB::table('assemblies')
            ->where('status', 'Good')
            ->where('assy_test_status', 'GOOD')
            ->whereNotNull('assy_doc_number')
            ->where(function($q) {
                $q->where('qc_status', 'PENDING_QC')
                  ->orWhere('qc_status', 'PENDING')
                  ->orWhereNull('qc_status');
            })
            ->count();

        // 2. Assembly results notif count (Hold, Scrap)
        $assyNotif = \Illuminate\Support\Facades\DB::table('assemblies')
            ->whereIn('status', ['Hold', 'Scrap'])
            ->where(function($q) {
                $q->where('is_reregistered', 0)->orWhereNull('is_reregistered');
            })
            ->count();

        // 3. Active production holds count
        $holdActive = \Illuminate\Support\Facades\DB::table('assemblies')
            ->where(function($q) {
                $q->whereIn('assy_test_status', ['Hold', 'HOLD', 'NOT_GOOD', 'Hold TF 1', 'HOLD TF 1'])
                  ->orWhereIn('qc_status', ['HOLD_ASSY', 'HOLD_QC', 'HOLD']);
            })
            ->count();

        // 4. Pending returned trays count
        $returnsPending = \Illuminate\Support\Facades\DB::table('trays')
            ->where('session_status', 'RETURNED_TO_WAREHOUSE')
            ->where(function($q) {
                $q->where('verified_by_gudang', 0)->orWhereNull('verified_by_gudang');
            })
            ->count();

        // 5. Warehouse receipts pending count
        $whReceiptPending = \Illuminate\Support\Facades\DB::table('assemblies')
            ->where('status', 'Good')
            ->where('qc_status', 'GOOD')
            ->whereNotNull('qc_doc_number')
            ->where(function($q) {
                $q->whereNull('warehouse_receipt_status')
                  ->orWhere('warehouse_receipt_status', 'PENDING');
            })
            ->count();

        // 6. Pending reassembly count
        $pendingReassembly = \Illuminate\Support\Facades\DB::table('assemblies')
            ->where('status', 'Hold')
            ->whereNotNull('rework_reason')
            ->where(function($q) {
                $q->where('is_reregistered', 0)->orWhereNull('is_reregistered');
            })
            ->count();

        // 7. Admin pending counts
        $adminPendingRequests = \Illuminate\Support\Facades\DB::table('re_register_requests')->where('status', 'PENDING')->count();
        $adminPendingSets = \Illuminate\Support\Facades\DB::table('assemblies')->where('status', 'Decline')->where('is_declined', 1)->count();
        $adminPendingReplacements = \Illuminate\Support\Facades\DB::table('part_replacement_requests')->where('status', 'PENDING')->count();
        $adminPendingTotal = $adminPendingRequests + $adminPendingSets + $adminPendingReplacements;

        return response()->json([
            'ok' => true,
            'counts' => [
                'qc_pending' => $qcPending,
                'assembly_notif' => $assyNotif,
                'hold_active' => $holdActive,
                'returns_pending' => $returnsPending,
                'wh_receipt_pending' => $whReceiptPending,
                'pending_reassembly' => $pendingReassembly,
                'admin_pending_requests' => $adminPendingRequests,
                'admin_pending_sets' => $adminPendingSets,
                'admin_pending_replacements' => $adminPendingReplacements,
                'admin_pending_total' => $adminPendingTotal,
            ]
        ]);
    }
}

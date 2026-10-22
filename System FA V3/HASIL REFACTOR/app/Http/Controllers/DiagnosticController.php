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
}

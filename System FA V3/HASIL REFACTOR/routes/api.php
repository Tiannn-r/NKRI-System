<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasterStockController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\AssemblyController;
use App\Http\Controllers\QcController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\AdminCmsController;
use App\Http\Controllers\DiagnosticController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistem Final Assembly & Traceability P9 (Refactored)
|--------------------------------------------------------------------------
| Seluruh endpoint API bersih tanpa duplikasi routing, mendukung konkurensi
| tinggi dengan InnoDB transaction, dan dilengkapi sistem monitor diagnostik.
*/

// ==========================================
// 1. AUTENTIKASI & PENGGUNA
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);
Route::get('/operators', [AuthController::class, 'getOperators']);
Route::get('/users/lookup', [AuthController::class, 'lookup']);

// Kelola Pengguna (Admin)
Route::prefix('admin')->group(function () {
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::post('/users', [AuthController::class, 'createUser']);
    Route::post('/users/update', [AuthController::class, 'updateUser']);
    Route::post('/users/delete', [AuthController::class, 'deleteUser']);
    Route::post('/users/reset-session', [AuthController::class, 'resetSession']);
    Route::post('/add-part', [MasterStockController::class, 'addPart']);
    Route::post('/add-part-bulk', [MasterStockController::class, 'addPartBulk']);
    Route::post('/delete-part', [MasterStockController::class, 'deletePart']);
    Route::get('/recent-added', [MasterStockController::class, 'recentAdded']);
});

// ==========================================
// 2. MASTER STOK (UN-CAPPED 18.000+ ITEMS)
// ==========================================
Route::prefix('master')->group(function () {
    Route::get('/available', [MasterStockController::class, 'getAvailableParts']);
    Route::get('/stocks_summary', [MasterStockController::class, 'getSummary']);
    Route::get('/stocks_list', [MasterStockController::class, 'getList']);
    Route::get('/parts', [MasterStockController::class, 'getParts']);
    Route::get('/part-detail', [MasterStockController::class, 'getPartDetail']);
    Route::get('/get-connected-set', [MasterStockController::class, 'getConnectedSet']);
    Route::post('/re-register', [MasterStockController::class, 'reRegister']);
    Route::post('/add-part', [MasterStockController::class, 'addPart']);
    Route::post('/add-part-bulk', [MasterStockController::class, 'addPartBulk']);
    Route::post('/delete-part', [MasterStockController::class, 'deletePart']);
    Route::get('/recent-added', [MasterStockController::class, 'recentAdded']);
});

// ==========================================
// 3. GUDANG & TRAY SUPPLY
// ==========================================
Route::get('/assembly/trays', [WarehouseController::class, 'getTrays']);
Route::post('/gudang/verify-tray', [WarehouseController::class, 'createTray']);
Route::get('/gudang/tray_detail', [WarehouseController::class, 'getTrayDetail']);
Route::get('/gudang/tray_verification_detail', [WarehouseController::class, 'getTrayDetail']);
Route::post('/tray/update_mo', [WarehouseController::class, 'updateMo']);
Route::post('/gudang/verify-return-tray', [WarehouseController::class, 'verifyTray']);
Route::post('/warehouse/verify-qc-receipt', [WarehouseController::class, 'verifyQcReceipt']);
Route::get('/gudang/returned-trays', [WarehouseController::class, 'getReturnedTrays']);

// ==========================================
// 4. ASSEMBLY WORKSTATION (MEJA PERAKITAN)
// ==========================================
Route::prefix('assembly')->group(function () {
    Route::post('/start-set', [AssemblyController::class, 'startSet']);
    Route::post('/save', [AssemblyController::class, 'saveAssembly']);
    Route::post('/submit-test', [AssemblyController::class, 'submitTest']); // TF 1: 17 Checklist Points
    Route::post('/pause', [AssemblyController::class, 'pause']);
    Route::post('/resume', [AssemblyController::class, 'resume']);
    Route::post('/replace-part', [AssemblyController::class, 'replacePart']);
    Route::get('/pending-reassembly', [AssemblyController::class, 'getPendingReassembly']);
    Route::post('/dispatch-reassembly', [AssemblyController::class, 'dispatchReassembly']);
});
Route::get('/admin/downtime-logs', [AssemblyController::class, 'getDowntimeLogs']);

// ==========================================
// 5. QUALITY CONTROL (QC / TF 2)
// Aturan Baku: Hanya Good atau Not Good (Hold)
// Tidak ada 17 pengujian di QC
// ==========================================
Route::get('/qc/items', [QcController::class, 'getItems']);
Route::get('/qc/pending-items', [QcController::class, 'getItems']);
Route::post('/qc/submit-test', [QcController::class, 'submitTest']);
Route::get('/production/hold-items', [QcController::class, 'getHoldItems']);
Route::post('/admin/approve-hold-item', [QcController::class, 'approveHoldItem']);

// ==========================================
// 6. LAPORAN & AUDIT TRAIL (4 MODUL LAPORAN)
// ==========================================
Route::get('/laporan', [ReportController::class, 'traveler']); // 1. Laporan WO Traveler
Route::prefix('reports')->group(function () {
    Route::get('/assy-tests', [ReportController::class, 'assyTests']); // 2. Laporan Uji Fungsional 1 (TF 1)
    Route::get('/qc-tests', [ReportController::class, 'qcTests']); // 3. Laporan Uji Mutu QC (TF 2)
    Route::get('/warehouse-receipts', [ReportController::class, 'warehouseReceipts']); // 4. Laporan Penerimaan Fisik Gudang
});

// ==========================================
// 7. DELIVERY ORDER, PACKING LIST & CLOUDINARY
// ==========================================
Route::prefix('delivery-orders')->group(function () {
    Route::get('/', [DeliveryOrderController::class, 'index']);
    Route::post('/', [DeliveryOrderController::class, 'store']);
    Route::get('/detail', [DeliveryOrderController::class, 'detail']);
    Route::post('/delete', [DeliveryOrderController::class, 'destroy']);
    Route::get('/next-meta', [DeliveryOrderController::class, 'nextMeta']);
    Route::get('/export-data', [DeliveryOrderController::class, 'exportData']); // Fixed clean single export
    Route::get('/settings', [AdminCmsController::class, 'getDoSettings']);
    Route::post('/settings', [AdminCmsController::class, 'updateDoSettings']);
    Route::post('/cloud-pdf', [DeliveryOrderController::class, 'uploadCloudPdf']);
    Route::post('/cloud-pdf/delete', [DeliveryOrderController::class, 'deleteCloudPdf']);
});
Route::get('/available-serial-numbers', [DeliveryOrderController::class, 'availableSerialNumbers']);
Route::get('/cloudinary/settings', [DeliveryOrderController::class, 'cloudinarySettings']);

// Master Customer untuk Surat Jalan
Route::prefix('customers')->group(function () {
    Route::get('/', [DeliveryOrderController::class, 'customers']);
    Route::post('/', [DeliveryOrderController::class, 'createCustomer']);
    Route::post('/update', [DeliveryOrderController::class, 'updateCustomer']);
    Route::post('/delete', [DeliveryOrderController::class, 'deleteCustomer']);
});

// Testing Points (17 Parameter TF 1)
Route::get('/testing-points', [AdminCmsController::class, 'getTestingPoints']);
Route::post('/admin/testing-points/update', [AdminCmsController::class, 'updateTestingPoint']);

// ==========================================
// 8. PORTAL CMS & DIAGNOSTIK ADMIN (/admin)
// ==========================================
Route::prefix('admin')->group(function () {
    // 3 Fitur Pembersihan Database & Manajemen Terpusat
    Route::post('/cms/clear-trays', [AdminCmsController::class, 'clearTrays']);
    Route::post('/cms/clear-downtimes', [AdminCmsController::class, 'clearDowntimes']);
    Route::post('/cms/clear-audit-logs', [AdminCmsController::class, 'clearAuditLogs']);
    Route::get('/cms/do-settings', [AdminCmsController::class, 'getDoSettings']);
    Route::post('/cms/do-settings', [AdminCmsController::class, 'updateDoSettings']);

    // Backend Error Logger
    Route::get('/error-logs', [DiagnosticController::class, 'getErrorLogs']);
    Route::post('/error-logs/clear', [DiagnosticController::class, 'clearErrorLogs']);

    // Slow Query & Lag Monitor (>500ms)
    Route::get('/slow-queries', [DiagnosticController::class, 'getSlowQueries']);
    Route::post('/slow-queries/clear', [DiagnosticController::class, 'clearSlowQueries']);

    // Data Anomaly Scanner & 1-Click Auto Repair
    Route::get('/anomalies/scan', [DiagnosticController::class, 'scanAnomalies']);
    Route::post('/anomalies/repair', [DiagnosticController::class, 'autoRepairAnomalies']);
});

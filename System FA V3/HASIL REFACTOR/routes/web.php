<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Final Assembly & Traceability P9 (Refactored)
|--------------------------------------------------------------------------
| Menyajikan frontend Vue 3 SPA secara langsung (response file statis)
| untuk performa super instan (<10ms) dan menghindari overhead parsing Blade
| pada file berukuran 39.000 baris.
*/

// Operational Single Page Application (Gudang, Assembly, TF1, TF2, Laporan, DO, Print)
Route::get('/', function () {
    return response()->file(public_path('app.html'));
});

// Dedicated Admin CMS Portal
Route::get('/admin', function () {
    return response()->file(public_path('app.html'));
});

// Standalone Login Page
Route::get('/login', function () {
    return response()->file(public_path('login.html'));
})->name('login');

// Catch-all route untuk Vue Single Page Application routing (kecuali /api/*)
Route::get('/{any}', function () {
    return response()->file(public_path('app.html'));
})->where('any', '^(?!api).*$');

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Final Assembly & Traceability P9 (MVC Architecture)
|--------------------------------------------------------------------------
*/

// Operational Single Page Application (Gudang, Assembly, TF1, TF2, Laporan, DO, Print)
Route::get('/', function () {
    return view('app');
});

// Dedicated Admin CMS Portal (Wajib Login Ulang Administrator)
Route::get('/admin', function () {
    return view('admin');
});

Route::get('/admin/login', function () {
    return view('admin');
});

// Standalone Login Page
Route::get('/login', function () {
    return view('login');
})->name('login');

// Redirect legacy .html urls to avoid broken bookmarks
Route::get('/app.html', function () {
    return redirect('/');
});

Route::get('/login.html', function () {
    return redirect('/login');
});

// Catch-all route untuk Vue Single Page Application routing (kecuali /api/*)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');

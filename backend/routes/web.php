<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Prescription PDF direct viewer/downloader
Route::get('/rx/{token}', [\App\Http\Controllers\Public\PublicBookingController::class, 'downloadPrescriptionPdf']);

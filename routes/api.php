<?php

use App\Http\Controllers\Api\DownloadController;
use Illuminate\Support\Facades\Route;

Route::post('/jobs', [DownloadController::class, 'store'])->name('jobs.store');
Route::get('/jobs/{download}', [DownloadController::class, 'show'])->name('jobs.show');
Route::get('/jobs/{download}/file', [DownloadController::class, 'file'])->name('jobs.file');

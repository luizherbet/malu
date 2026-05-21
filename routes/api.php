<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\PlaylistController;
use Illuminate\Support\Facades\Route;

Route::get('/config', AppConfigController::class)->name('config');

Route::middleware('web')->group(function (): void {
    Route::get('/auth/user', [AuthController::class, 'user'])->name('auth.user');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('guest')->name('auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('guest')->name('auth.register');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');
});

Route::middleware(['web', 'malu.auth'])->group(function (): void {
    Route::middleware('throttle:downloads-read')->group(function (): void {
        Route::post('/playlists/preview', [PlaylistController::class, 'preview'])->name('playlists.preview');
    });

    Route::middleware('throttle:downloads-store')->group(function (): void {
        Route::post('/jobs', [DownloadController::class, 'store'])->name('jobs.store');
    });

    Route::middleware('throttle:downloads-read')->group(function (): void {
        Route::get('/jobs/{download}', [DownloadController::class, 'show'])->name('jobs.show');
        Route::get('/jobs/{download}/file', [DownloadController::class, 'file'])->name('jobs.file');
    });
});

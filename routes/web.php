<?php

use App\Http\Controllers\ActiveBusinessController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredOwnerController;
use App\Http\Controllers\BusinessInvitationCodeController;
use App\Http\Controllers\OwnerOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredOwnerController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredOwnerController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', OwnerOverviewController::class)
        ->middleware('active.business')
        ->name('overview');

    Route::post('/businesses/{business}/invitation-code', [BusinessInvitationCodeController::class, 'store'])
        ->name('businesses.invitation-code.store');

    Route::post('/businesses/{business}/activate', [ActiveBusinessController::class, 'store'])
        ->name('businesses.activate');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

<?php

use App\Http\Controllers\ActiveBusinessController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmployeeAuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredEmployeeController;
use App\Http\Controllers\Auth\RegisteredOwnerController;
use App\Http\Controllers\BusinessInvitationCodeController;
use App\Http\Controllers\Employee\BusinessMembershipController as EmployeeBusinessMembershipController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\SalesImportController;
use App\Http\Controllers\Employee\TaskOccurrenceProgressController;
use App\Http\Controllers\Owner\TaskController;
use App\Http\Controllers\Owner\TaskOccurrenceController;
use App\Http\Controllers\OwnerOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredOwnerController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredOwnerController::class, 'store'])->name('register.store');

    Route::get('/employee/login', [EmployeeAuthenticatedSessionController::class, 'create'])
        ->name('employee.login');
    Route::post('/employee/login', [EmployeeAuthenticatedSessionController::class, 'store'])
        ->name('employee.login.store');
    Route::get('/employee/register', [RegisteredEmployeeController::class, 'create'])
        ->name('employee.register');
    Route::post('/employee/register', [RegisteredEmployeeController::class, 'store'])
        ->name('employee.register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', OwnerOverviewController::class)
        ->middleware('active.business')
        ->name('overview');

    Route::middleware('active.business')->group(function (): void {
        Route::resource('tasks', TaskController::class)->except(['show', 'destroy']);
        Route::get('/task-monitoring', TaskOccurrenceController::class)->name('task-occurrences.index');
    });

    Route::post('/businesses/{business}/invitation-code', [BusinessInvitationCodeController::class, 'store'])
        ->name('businesses.invitation-code.store');

    Route::post('/businesses/{business}/activate', [ActiveBusinessController::class, 'store'])
        ->name('businesses.activate');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('employee')->name('employee.')->group(function (): void {
        Route::get('/join-business', [EmployeeBusinessMembershipController::class, 'create'])
            ->name('business.join.create');
        Route::post('/join-business', [EmployeeBusinessMembershipController::class, 'store'])
            ->name('business.join.store');
        Route::get('/', EmployeeDashboardController::class)
            ->middleware('active.employee.business')
            ->name('dashboard');
        Route::post('/sales-imports', SalesImportController::class)
            ->middleware('active.employee.business')
            ->name('sales-imports.store');
        Route::patch('/tasks/{taskOccurrence}', [TaskOccurrenceProgressController::class, 'update'])
            ->middleware('active.employee.business')
            ->name('tasks.update');
        Route::post('/logout', [EmployeeAuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});

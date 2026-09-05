<?php

use App\Http\Controllers\Account\AccountSettingsController;
use App\Http\Controllers\Account\OtherBrowserSessionController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\PendingEmailController;
use App\Http\Controllers\Account\PendingEmailVerificationController;
use App\Http\Controllers\Account\ProfileAvatarController;
use App\Http\Controllers\Account\ProfileController;
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
use App\Http\Controllers\Owner\SalesController;
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
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:account-sensitive')
        ->name('profile.update');
    Route::patch('/profile/avatar', [ProfileAvatarController::class, 'update'])
        ->middleware('throttle:account-sensitive')
        ->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileAvatarController::class, 'destroy'])
        ->middleware('throttle:account-sensitive')
        ->name('profile.avatar.destroy');

    Route::get('/account/settings', [AccountSettingsController::class, 'show'])->name('account.settings');
    Route::patch('/account/email', [PendingEmailController::class, 'update'])
        ->middleware('throttle:account-sensitive')
        ->name('account.email.update');
    Route::post('/account/email/verification-notification', [PendingEmailController::class, 'store'])
        ->middleware('throttle:email-verification')
        ->name('account.email.verification.send');
    Route::get('/account/email/verify/{user}', PendingEmailVerificationController::class)
        ->middleware(['signed', 'throttle:email-verification'])
        ->name('account.email.verify');
    Route::patch('/account/password', [PasswordController::class, 'update'])
        ->middleware('throttle:account-sensitive')
        ->name('account.password.update');
    Route::delete('/account/sessions/others', [OtherBrowserSessionController::class, 'destroy'])
        ->middleware('throttle:account-sensitive')
        ->name('account.sessions.destroy-others');

    Route::get('/', OwnerOverviewController::class)
        ->middleware('active.business')
        ->name('overview');

    Route::middleware('active.business')->group(function (): void {
        Route::get('/sales', SalesController::class)->name('sales.index');
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

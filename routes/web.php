<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Receptionist\DashboardController as ReceptionistDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:auth-login')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', HomeController::class)->name('dashboard');

    Route::middleware('role:super_admin,event_admin')->group(function (): void {
        Route::get('/admin', AdminDashboardController::class)->name('admin.dashboard');
    });

    Route::middleware('role:receptionist')->group(function (): void {
        Route::get('/receptionist', ReceptionistDashboardController::class)->name('receptionist.dashboard');
    });
});

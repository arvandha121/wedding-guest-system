<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventMemberController;
use App\Http\Controllers\Admin\EventSessionController;
use App\Http\Controllers\Admin\UserController;
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

    Route::middleware('role:super_admin,event_admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('events', [EventController::class, 'store'])->name('events.store');
        Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::patch('events/{event}/status', [EventController::class, 'status'])->name('events.status');
        Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

        Route::post('events/{event}/sessions', [EventSessionController::class, 'store'])->name('sessions.store');
        Route::put('sessions/{eventSession}', [EventSessionController::class, 'update'])->name('sessions.update');
        Route::delete('sessions/{eventSession}', [EventSessionController::class, 'destroy'])->name('sessions.destroy');

        Route::post('events/{event}/members', [EventMemberController::class, 'store'])->name('members.store');
        Route::delete('events/{event}/members/{user}', [EventMemberController::class, 'destroy'])->name('members.destroy');
        Route::post('events/{event}/receptionists', [EventMemberController::class, 'storeReceptionist'])->name('receptionists.store');

        Route::middleware('role:super_admin')->prefix('users')->name('users.')->group(function (): void {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('{user}', [UserController::class, 'update'])->name('update');
            Route::patch('{user}/status', [UserController::class, 'status'])->name('status');
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });

    Route::middleware('role:receptionist')->group(function (): void {
        Route::get('/receptionist', ReceptionistDashboardController::class)->name('receptionist.dashboard');
    });
});

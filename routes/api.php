<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventMemberController;
use App\Http\Controllers\Api\V1\EventSessionController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
| Seluruh route di file ini berada di bawah prefix /api/v1 (lihat bootstrap/app.php).
*/
Route::name('api.v1.')->group(function (): void {
    Route::get('health', HealthController::class)->middleware('throttle:api')->name('health');

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login')
            ->name('login');

        Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        });
    });

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function (): void {
        // Manajemen pengguna: hanya Super Admin
        Route::middleware('role:super_admin')->prefix('users')->name('users.')->group(function (): void {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('{user}', [UserController::class, 'show'])->name('show');
            Route::put('{user}', [UserController::class, 'update'])->name('update');
            Route::patch('{user}/status', [UserController::class, 'status'])->name('status');
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        // Acara: akses dibatasi policy + route binding (404 untuk acara di luar penugasan)
        Route::prefix('events')->name('events.')->group(function (): void {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::post('{trashedEvent}/restore', [EventController::class, 'restore'])->name('restore');
            Route::get('{event}', [EventController::class, 'show'])->name('show');
            Route::put('{event}', [EventController::class, 'update'])->name('update');
            Route::patch('{event}/status', [EventController::class, 'status'])->name('status');
            Route::delete('{event}', [EventController::class, 'destroy'])->name('destroy');

            Route::get('{event}/sessions', [EventSessionController::class, 'index'])->name('sessions.index');
            Route::post('{event}/sessions', [EventSessionController::class, 'store'])->name('sessions.store');

            Route::get('{event}/members', [EventMemberController::class, 'index'])->name('members.index');
            Route::post('{event}/members', [EventMemberController::class, 'store'])->name('members.store');
            Route::delete('{event}/members/{user}', [EventMemberController::class, 'destroy'])->name('members.destroy');
            Route::post('{event}/receptionists', [EventMemberController::class, 'storeReceptionist'])->name('receptionists.store');

            Route::get('{event}/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('{event}/categories', [CategoryController::class, 'store'])->name('categories.store');

            Route::get('{event}/invitations', [InvitationController::class, 'index'])->name('invitations.index');
            Route::post('{event}/invitations', [InvitationController::class, 'store'])->name('invitations.store');
            Route::post('{event}/invitations/send', [InvitationController::class, 'sendAll'])->name('invitations.send');

            Route::get('{event}/guests', [GuestController::class, 'index'])->name('guests.index');
            Route::post('{event}/guests', [GuestController::class, 'store'])->name('guests.store');
            Route::post('{event}/guests/bulk-delete', [GuestController::class, 'bulkDelete'])->name('guests.bulk-delete');
            Route::post('{event}/guests/bulk-category', [GuestController::class, 'bulkCategory'])->name('guests.bulk-category');
            Route::get('{event}/guests/import-template', [GuestController::class, 'template'])->name('guests.template');
            Route::post('{event}/guests/import', [GuestController::class, 'import'])->name('guests.import');
            Route::get('{event}/guests/export', [GuestController::class, 'export'])->name('guests.export');
        });

        Route::get('invitations/{invitation}', [InvitationController::class, 'show'])->name('invitations.show');
        Route::put('invitations/{invitation}/sessions', [InvitationController::class, 'sessions'])->name('invitations.sessions');
        Route::post('invitations/{invitation}/generate-qr', [InvitationController::class, 'regenerate'])->name('invitations.regenerate');
        Route::post('invitations/{invitation}/revoke', [InvitationController::class, 'revoke'])->name('invitations.revoke');
        Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::get('invitations/{invitation}/deliveries', [InvitationController::class, 'deliveries'])->name('invitations.deliveries');
        Route::get('invitations/{invitation}/qr.webp', [InvitationController::class, 'qr'])->name('invitations.qr');

        Route::get('guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::put('guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::put('sessions/{eventSession}', [EventSessionController::class, 'update'])->name('sessions.update');
        Route::delete('sessions/{eventSession}', [EventSessionController::class, 'destroy'])->name('sessions.destroy');
    });
});

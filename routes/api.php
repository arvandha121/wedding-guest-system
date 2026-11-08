<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
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
        Route::get('users', [UserController::class, 'index'])
            ->middleware('role:super_admin')
            ->name('users.index');
    });
});

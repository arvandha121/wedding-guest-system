<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at']->toIso8601String(),
            'user' => UserResource::make($result['user'])->resolve($request),
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(null, 'Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            UserResource::make($request->user())->resolve($request),
            'Profil berhasil diambil.',
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken();

        if (! $current instanceof PersonalAccessToken) {
            throw new ApiException('Refresh hanya tersedia untuk token API.', 'TOKEN_REQUIRED', 400);
        }

        $result = $this->auth->refresh($request->user(), $current);

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at']->toIso8601String(),
        ], 'Token berhasil diperbarui.');
    }
}

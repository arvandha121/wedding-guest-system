<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    /**
     * @return array{user: User, token: string, expires_at: CarbonInterface}
     */
    public function login(string $email, string $password, ?string $deviceName): array
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->first();

        if ($user === null) {
            // Samakan waktu respons agar keberadaan email tidak bisa ditebak.
            Hash::make(Str::random(16));
            throw $this->invalidCredentials();
        }

        if (! Hash::check($password, $user->password)) {
            throw $this->invalidCredentials();
        }

        if (! $user->isActive()) {
            throw new ApiException('Akun Anda tidak aktif. Hubungi administrator.', 'ACCOUNT_INACTIVE', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return ['user' => $user] + $this->issueToken($user, $deviceName);
    }

    /**
     * Rotasi token: token lama dicabut, token baru diterbitkan dengan nama yang sama.
     *
     * @return array{token: string, expires_at: CarbonInterface}
     */
    public function refresh(User $user, PersonalAccessToken $current): array
    {
        return DB::transaction(function () use ($user, $current): array {
            $new = $this->issueToken($user, $current->name);
            $current->delete();

            return $new;
        });
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * @return array{token: string, expires_at: CarbonInterface}
     */
    private function issueToken(User $user, ?string $deviceName): array
    {
        $expiresAt = now()->addMinutes($user->role->tokenTtlMinutes());
        $name = filled($deviceName) ? $deviceName : 'api';

        $newToken = $user->createToken($name, [$user->role->value], $expiresAt);

        return ['token' => $newToken->plainTextToken, 'expires_at' => $expiresAt];
    }

    private function invalidCredentials(): ApiException
    {
        return new ApiException('Email atau kata sandi salah.', 'INVALID_CREDENTIALS', 401);
    }
}

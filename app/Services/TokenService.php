<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Token QR/undangan: acak, tanpa data pribadi. Database hanya menyimpan HMAC (untuk pencarian)
 * dan salinan terenkripsi (agar QR dan tautan bisa ditampilkan ulang).
 */
final class TokenService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function qrToken(): string
    {
        return 'WGS-' . bin2hex(random_bytes(24));
    }

    public function publicToken(): string
    {
        return Str::random(40);
    }

    public function code(): string
    {
        $code = '';
        $max = strlen(self::CODE_ALPHABET) - 1;

        for ($i = 0; $i < 8; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    public function hash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    public function encrypt(string $token): string
    {
        return Crypt::encryptString($token);
    }

    public function decrypt(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }
}

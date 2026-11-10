<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class Normalizer
{
    /** Huruf kecil, tanpa tanda baca, spasi dirapatkan. */
    public static function name(string $name): string
    {
        return (string) Str::of($name)->lower()->replaceMatches('/[^\p{L}\p{N}\s]/u', '')->squish();
    }

    /** Nomor telepon Indonesia ke format 62xxxxxxxx; null bila tidak masuk akal. */
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return strlen($digits) >= 8 ? $digits : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleMapsUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;
        $host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';
        $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';

        $valid = $scheme === 'https' && (
            $host === 'maps.app.goo.gl'
            || $host === 'goo.gl'
            || preg_match('/(^|\.)google\.[a-z.]+$/', $host) === 1
        );

        if (! $valid) {
            $fail('Tautan harus berupa URL Google Maps (https).');
        }
    }
}

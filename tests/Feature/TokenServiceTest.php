<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TokenService;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    public function test_tokens_are_random_prefixed_and_hash_is_deterministic(): void
    {
        $tokens = app(TokenService::class);

        $a = $tokens->qrToken();
        $b = $tokens->qrToken();

        $this->assertStringStartsWith('WGS-', $a);
        $this->assertNotSame($a, $b);
        $this->assertSame($tokens->hash($a), $tokens->hash($a));
        $this->assertNotSame($tokens->hash($a), $tokens->hash($b));
        $this->assertSame(64, strlen($tokens->hash($a)));
    }

    public function test_encrypted_copy_round_trips_and_code_uses_safe_alphabet(): void
    {
        $tokens = app(TokenService::class);
        $token = $tokens->qrToken();

        $this->assertNotSame($token, $tokens->encrypt($token));
        $this->assertSame($token, $tokens->decrypt($tokens->encrypt($token)));
        $this->assertMatchesRegularExpression('/^[A-HJKMNP-Z2-9]{8}$/', $tokens->code());
    }
}

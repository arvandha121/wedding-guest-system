<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Normalizer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    public function test_phone_variants_normalize_to_the_same_value(): void
    {
        foreach (['0812-3456-7890', '+62 812 3456 7890', '6281234567890', '81234567890'] as $input) {
            $this->assertSame('6281234567890', Normalizer::phone($input), $input);
        }
    }

    public function test_implausible_phone_numbers_become_null(): void
    {
        $this->assertNull(Normalizer::phone('123'));
        $this->assertNull(Normalizer::phone(''));
        $this->assertNull(Normalizer::phone(null));
    }

    public function test_name_is_lowercased_without_punctuation_and_extra_spaces(): void
    {
        $this->assertSame('budi santoso st', Normalizer::name('  Budi   Santoso, S.T. '));
    }
}

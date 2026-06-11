<?php

namespace Tests\Unit;

use App\Support\Feeder\HandphoneNormalizer;
use PHPUnit\Framework\TestCase;

class HandphoneNormalizerTest extends TestCase
{
    public function test_nim_as_eight_digit_handphone_gets_08_prefix(): void
    {
        $this->assertSame('0825222067', HandphoneNormalizer::forFeeder('25222067', '25222067'));
    }

    public function test_full_mobile_number_is_preserved(): void
    {
        $this->assertSame('08825222067', HandphoneNormalizer::forFeeder('08825222067', '25222067'));
    }

    public function test_placeholder_handphone_uses_nim_fallback(): void
    {
        $this->assertSame('0825222067', HandphoneNormalizer::forFeeder('000000000000', '25222067'));
    }

    public function test_empty_handphone_uses_nim_fallback(): void
    {
        $this->assertSame('0825222067', HandphoneNormalizer::forFeeder(null, '25222067'));
    }

    public function test_candidates_include_prefix_variants(): void
    {
        $candidates = HandphoneNormalizer::candidates('25222067', '25222067');

        $this->assertContains('0825222067', $candidates);
        $this->assertContains('08825222067', $candidates);
    }
}

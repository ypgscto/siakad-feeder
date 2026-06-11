<?php

namespace App\Support\Feeder;

final class NilaiIndeksCalculator
{
    public static function fromAngka(float $nilaiAngka): ?string
    {
        if ($nilaiAngka >= 85.0 && $nilaiAngka <= 100.0) {
            return '4.00';
        }
        if ($nilaiAngka >= 69.0 && $nilaiAngka < 85.0) {
            return '3.00';
        }
        if ($nilaiAngka >= 53.0 && $nilaiAngka < 69.0) {
            return '2.00';
        }
        if ($nilaiAngka >= 37.0 && $nilaiAngka < 53.0) {
            return '1.00';
        }
        if ($nilaiAngka >= 0.0 && $nilaiAngka < 37.0) {
            return '0.00';
        }

        return null;
    }
}

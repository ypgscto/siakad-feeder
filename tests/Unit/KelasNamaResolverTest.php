<?php

namespace Tests\Unit;

use App\Support\Feeder\KelasNamaResolver;
use Tests\TestCase;

class KelasNamaResolverTest extends TestCase
{
    public function test_for_feeder_prefers_kelas_nama_over_internal_id(): void
    {
        $this->assertSame('3A', KelasNamaResolver::forFeeder([
            'nama_kelas' => '382',
            'kelas_nama' => '3A',
        ]));
    }

    public function test_for_feeder_falls_back_to_nama_kelas(): void
    {
        $this->assertSame('382', KelasNamaResolver::forFeeder([
            'nama_kelas' => '382',
        ]));
    }

    public function test_from_request_prefers_kelas_nama(): void
    {
        $this->assertSame('3B', KelasNamaResolver::fromRequest('3B', '383'));
    }
}

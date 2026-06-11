<?php

namespace Tests\Unit;

use App\Support\Feeder\TanggalDaftarResolver;
use Tests\TestCase;

class TanggalDaftarResolverTest extends TestCase
{
    public function test_genap_20252_uses_february_next_year(): void
    {
        $this->assertSame('2026-02-01', TanggalDaftarResolver::fromTahunId('20252'));
    }

    public function test_ganjil_20251_uses_september_same_year(): void
    {
        $this->assertSame('2025-09-01', TanggalDaftarResolver::fromTahunId('20251'));
    }

    public function test_prefers_tgl_kuliah_mulai_from_siakad(): void
    {
        $date = TanggalDaftarResolver::resolve([
            'tahun_id' => '20252',
            'tgl_kuliah_mulai' => '2026-02-10',
        ]);

        $this->assertSame('2026-02-10', $date);
    }

    public function test_old_buggy_date_for_genap_would_be_wrong(): void
    {
        $this->assertNotSame('2025-09-01', TanggalDaftarResolver::fromTahunId('20252'));
    }
}

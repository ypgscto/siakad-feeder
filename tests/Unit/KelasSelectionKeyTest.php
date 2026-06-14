<?php

namespace Tests\Unit;

use App\Support\Sync\KelasSelectionKey;
use Tests\TestCase;

class KelasSelectionKeyTest extends TestCase
{
    public function test_uses_jadwal_id_not_mk_kode(): void
    {
        $a = KelasSelectionKey::fromRow(['id' => '2480', 'mk_kode' => 'S121103', 'nama_kelas' => '377']);
        $b = KelasSelectionKey::fromRow(['id' => '2552', 'mk_kode' => 'S121103', 'nama_kelas' => '377']);

        $this->assertSame('2480', $a);
        $this->assertSame('2552', $b);
        $this->assertNotSame($a, $b);
    }
}

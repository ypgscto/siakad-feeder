<?php

namespace App\Services\Feeder;

class FeederLookupService
{
    public function __construct(
        protected FeederClient $feeder,
    ) {}

    public function idRegistrasiMahasiswaByNim(string $nim): ?string
    {
        $nim = str_replace("'", '', trim($nim));
        if ($nim === '') {
            return null;
        }

        $rows = $this->feeder->getList(
            'GetListRiwayatPendidikanMahasiswa',
            "nim = '{$nim}'",
        );

        return isset($rows[0]['id_registrasi_mahasiswa'])
            ? (string) $rows[0]['id_registrasi_mahasiswa']
            : null;
    }

    public function idKelasKuliah(string $tahunId, string $mkKode, string $namaKelas): ?string
    {
        $tahunId = str_replace("'", '', trim($tahunId));
        $mkKode = str_replace("'", '', trim($mkKode));
        $namaKelas = str_replace("'", '', trim($namaKelas));

        $rows = $this->feeder->getList(
            'GetListKelasKuliah',
            "id_semester = '{$tahunId}' and nama_kelas_kuliah = '{$namaKelas}' and kode_mata_kuliah = '{$mkKode}'",
        );

        return isset($rows[0]['id_kelas_kuliah'])
            ? (string) $rows[0]['id_kelas_kuliah']
            : null;
    }

    public function idRegistrasiDosenByNidn(string $nidn): ?string
    {
        $nidn = str_replace("'", '', trim($nidn));
        if ($nidn === '') {
            return null;
        }

        $rows = $this->feeder->getList(
            'GetListPenugasanDosen',
            "nidn = '{$nidn}'",
        );

        return isset($rows[0]['id_registrasi_dosen'])
            ? (string) $rows[0]['id_registrasi_dosen']
            : null;
    }

    public function idMatkulByKode(string $mkKode): ?string
    {
        $mkKode = str_replace("'", '', trim($mkKode));
        if ($mkKode === '') {
            return null;
        }

        $rows = $this->feeder->getList(
            'GetListMataKuliah',
            "kode_mata_kuliah = '{$mkKode}'",
            10,
        );

        foreach ($rows as $row) {
            if (($row['kode_mata_kuliah'] ?? '') === $mkKode) {
                return (string) ($row['id_matkul'] ?? $row['id_mata_kuliah'] ?? '');
            }
        }

        return isset($rows[0]['id_matkul'])
            ? (string) $rows[0]['id_matkul']
            : (isset($rows[0]['id_mata_kuliah']) ? (string) $rows[0]['id_mata_kuliah'] : null);
    }
}

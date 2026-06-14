<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\LoadsAcademicMaster;
use App\Http\Controllers\Controller;
use App\Services\SiakadApiService;
use App\Services\Sync\NilaiFeederService;
use App\Support\Feeder\KelasNamaResolver;
use App\Support\Sync\SyncFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NilaiController extends Controller
{
    use LoadsAcademicMaster;

    public function index(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);
        $classes = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $classes = $siakad->fetchClasses(array_filter([
                    'tahun_id' => $filters['tahun_id'],
                ]));
                if ($filters['prodi_id'] !== '') {
                    $classes = array_values(array_filter(
                        $classes,
                        fn (array $r) => ($r['prodi_kode'] ?? '') === $filters['prodi_id'],
                    ));
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.nilai.index', [
            'title' => 'Nilai Perkuliahan',
            'filters' => $filters,
            'master' => $master,
            'classes' => $classes,
            'error' => $error,
            'loaded' => $loaded,
        ]);
    }

    public function peserta(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);
        $jadwalId = $request->string('jadwal_id')->toString();
        $mkKode = $request->string('mk_kode')->toString();
        $namaKelas = $request->string('nama_kelas')->toString();
        $kelasNama = $request->string('kelas_nama')->toString();

        $participants = [];
        $error = $master['error'];

        if ($jadwalId !== '' && $error === null) {
            try {
                $participants = $siakad->fetchClassParticipants([
                    'jadwal_id' => $jadwalId,
                    'tahun_id' => $filters['tahun_id'],
                    'prodi_id' => $filters['prodi_id'],
                    'mk_kode' => $mkKode,
                    'nama_kelas' => $namaKelas,
                ]);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.nilai.peserta', [
            'title' => 'Nilai Peserta Kelas',
            'filters' => $filters,
            'jadwalId' => $jadwalId,
            'mkKode' => $mkKode,
            'namaKelas' => $namaKelas,
            'kelasNama' => KelasNamaResolver::fromRequest($kelasNama, $namaKelas),
            'participants' => $participants,
            'error' => $error,
        ]);
    }

    public function send(Request $request, SiakadApiService $siakad, NilaiFeederService $sync): RedirectResponse
    {
        @set_time_limit(900);

        $validated = $request->validate([
            'prodi_id' => ['required', 'string'],
            'tahun_id' => ['required', 'string'],
            'jadwal_id' => ['required', 'string'],
            'mk_kode' => ['required', 'string'],
            'nama_kelas' => ['required', 'string'],
            'kelas_nama' => ['nullable', 'string'],
            'only_selected' => ['nullable', 'boolean'],
            'nims' => ['nullable', 'array'],
        ]);

        $validated['prodi_id'] = $this->enforceProdiOnFilters([
            'prodi_id' => (string) $validated['prodi_id'],
        ])['prodi_id'];

        $participants = $siakad->fetchClassParticipants([
            'jadwal_id' => $validated['jadwal_id'],
            'tahun_id' => $validated['tahun_id'],
            'prodi_id' => $validated['prodi_id'],
            'mk_kode' => $validated['mk_kode'],
            'nama_kelas' => $validated['nama_kelas'],
        ]);

        if ($request->boolean('only_selected')) {
            $nims = array_filter((array) $request->input('nims', []), 'is_string');
            $participants = array_values(array_filter(
                $participants,
                fn (array $r) => in_array((string) ($r['nim'] ?? ''), $nims, true),
            ));
        }

        $kelasNamaFeeder = KelasNamaResolver::fromRequest(
            (string) ($validated['kelas_nama'] ?? ''),
            $validated['nama_kelas'],
        );

        try {
            $result = $sync->updateNilaiKelas(
                $participants,
                $validated['tahun_id'],
                $validated['mk_kode'],
                $kelasNamaFeeder,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.nilai.peserta', $validated)
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->route('admin.nilai.peserta', $validated);

        return SyncFlash::apply($redirect, $result, 'Kirim nilai', 'nilai');
    }
}

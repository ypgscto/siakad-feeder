<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\LoadsAcademicMaster;
use App\Http\Controllers\Controller;
use App\Services\Feeder\FeederLookupService;
use App\Services\SiakadApiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DosenController extends Controller
{
    use LoadsAcademicMaster;

    public function index(Request $request, SiakadApiService $siakad, FeederLookupService $lookup): View
    {
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);

        $lecturers = [];
        $feederStatus = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $lecturers = $siakad->fetchLecturers();
                if ($filters['prodi_id'] !== '') {
                    $lecturers = array_values(array_filter(
                        $lecturers,
                        fn (array $r) => ($r['prodi_kode'] ?? '') === $filters['prodi_id'],
                    ));
                }

                if ($request->boolean('cek_feeder')) {
                    foreach (array_slice($lecturers, 0, 30) as $row) {
                        $nidn = (string) ($row['nidn'] ?? '');
                        if ($nidn === '') {
                            $feederStatus[$row['id'] ?? ''] = 'tanpa NIDN';

                            continue;
                        }
                        try {
                            $id = $lookup->idRegistrasiDosenByNidn($nidn);
                            $feederStatus[$nidn] = $id ? 'terdaftar' : 'belum';
                        } catch (RuntimeException) {
                            $feederStatus[$nidn] = 'error';
                        }
                    }
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.dosen.index', [
            'title' => 'Data Dosen',
            'filters' => $filters,
            'master' => $master,
            'lecturers' => $lecturers,
            'feederStatus' => $feederStatus,
            'error' => $error,
            'loaded' => $loaded,
            'cekFeeder' => $request->boolean('cek_feeder'),
        ]);
    }
}

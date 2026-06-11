<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeederCodeMap;
use App\Models\FeederProdiMap;
use App\Services\Feeder\FeederCodeMapService;
use App\Services\SiakadApiService;
use App\Support\Feeder\Sifeeder2MappingScan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class FeederMappingController extends Controller
{
    public function index(SiakadApiService $siakad, FeederCodeMapService $codeMaps): View
    {
        $siakadMaster = $this->fetchSiakadMasters($siakad);

        return view('admin.mapping.index', [
            'title' => 'Pemetaan Siakad ↔ Neo Feeder',
            'scanFindings' => Sifeeder2MappingScan::findings(),
            'prodiMaps' => FeederProdiMap::query()->orderBy('siakad_prodi_id')->get(),
            'codeMaps' => $codeMaps->listGrouped(),
            'feederReference' => config('feeder_reference', []),
            'constants' => $this->constants(),
            'siakadMaster' => $siakadMaster,
            'categories' => FeederCodeMap::categories(),
            'siakadReference' => config('siakad_reference', []),
        ]);
    }

    public function storeProdi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siakad_prodi_id' => ['required', 'string', 'max:120'],
            'feeder_id_prodi' => ['required', 'uuid'],
            'feeder_id_prodi_asal' => ['nullable', 'uuid'],
            'feeder_id_prodi_rpl' => ['nullable', 'uuid'],
        ]);

        FeederProdiMap::query()->updateOrCreate(
            ['siakad_prodi_id' => trim($validated['siakad_prodi_id'])],
            [
                'feeder_id_prodi' => $validated['feeder_id_prodi'],
                'feeder_id_prodi_asal' => $validated['feeder_id_prodi_asal'] ?? null,
                'feeder_id_prodi_rpl' => $validated['feeder_id_prodi_rpl'] ?? null,
                'is_active' => true,
            ],
        );

        return redirect()->route('admin.mapping.index', ['tab' => 'prodi'])
            ->with('success', 'Pemetaan prodi disimpan.');
    }

    public function destroyProdi(FeederProdiMap $prodiMap): RedirectResponse
    {
        $prodiMap->delete();

        return redirect()->route('admin.mapping.index', ['tab' => 'prodi'])
            ->with('success', 'Pemetaan prodi dihapus.');
    }

    public function storeCodeMap(Request $request, FeederCodeMapService $codeMaps): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', FeederCodeMap::categories())],
            'siakad_key' => ['required', 'string', 'max:120'],
            'siakad_label' => ['nullable', 'string', 'max:200'],
            'feeder_value' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $category = $validated['category'];
        $feederValue = trim($validated['feeder_value']);

        FeederCodeMap::query()->updateOrCreate(
            [
                'category' => $category,
                'siakad_key' => trim($validated['siakad_key']),
            ],
            [
                'siakad_label' => $validated['siakad_label'] ?? trim($validated['siakad_key']),
                'feeder_value' => $feederValue,
                'feeder_label' => $codeMaps->feederLabel($category, $feederValue),
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ],
        );

        return redirect()->route('admin.mapping.index', ['tab' => $category])
            ->with('success', 'Pemetaan kode disimpan.');
    }

    public function destroyCodeMap(FeederCodeMap $codeMap): RedirectResponse
    {
        $tab = $codeMap->category;
        $codeMap->delete();

        return redirect()->route('admin.mapping.index', ['tab' => $tab])
            ->with('success', 'Pemetaan kode dihapus.');
    }

    public function syncFromSiakad(SiakadApiService $siakad, FeederCodeMapService $codeMaps): RedirectResponse
    {
        $created = 0;

        try {
            foreach ($siakad->fetchStatusAwal() as $row) {
                $key = (string) ($row['id'] ?? '');
                if ($key === '') {
                    continue;
                }
                $created += $this->ensureCodeMapRow(
                    $codeMaps,
                    FeederCodeMap::CATEGORY_JENIS_DAFTAR,
                    $key,
                    (string) ($row['nama'] ?? $key),
                    config("feeder_maps.jenis_daftar.{$key}"),
                );
            }

            foreach ($siakad->fetchGraduationStatus() as $row) {
                $key = (string) ($row['id'] ?? '');
                if ($key === '') {
                    continue;
                }
                $created += $this->ensureCodeMapRow(
                    $codeMaps,
                    FeederCodeMap::CATEGORY_JENIS_KELUAR,
                    $key,
                    (string) ($row['nama'] ?? $key),
                    config("feeder_maps.jenis_keluar.{$key}"),
                );
            }
        } catch (RuntimeException $e) {
            return redirect()->route('admin.mapping.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.mapping.index')
            ->with('success', "Sinkron dari Siakad-API selesai. {$created} baris baru ditambahkan (yang belum ada).");
    }

    protected function ensureCodeMapRow(
        FeederCodeMapService $codeMaps,
        string $category,
        string $key,
        string $label,
        mixed $configValue,
    ): int {
        $exists = FeederCodeMap::query()
            ->where('category', $category)
            ->where('siakad_key', $key)
            ->exists();

        if ($exists) {
            return 0;
        }

        $feederValue = is_string($configValue) && $configValue !== ''
            ? $configValue
            : (string) config('feeder_maps.jenis_keluar.default', '1');

        if ($category === FeederCodeMap::CATEGORY_JENIS_DAFTAR && (! is_string($configValue) || $configValue === '')) {
            $feederValue = '';
        }

        FeederCodeMap::query()->create([
            'category' => $category,
            'siakad_key' => $key,
            'siakad_label' => $label,
            'feeder_value' => $feederValue,
            'feeder_label' => $feederValue !== '' ? $codeMaps->feederLabel($category, $feederValue) : null,
            'notes' => 'Auto dari Siakad-API',
            'is_active' => $feederValue !== '',
        ]);

        return 1;
    }

    /**
     * @return array{
     *   prodi: list<array<string, mixed>>,
     *   status_awal: list<array<string, mixed>>,
     *   status_lulus: list<array<string, mixed>>,
     *   error: string|null
     * }
     */
    protected function fetchSiakadMasters(SiakadApiService $siakad): array
    {
        $master = [
            'prodi' => [],
            'status_awal' => [],
            'status_lulus' => [],
            'error' => null,
        ];

        try {
            $master['prodi'] = $siakad->fetchStudyPrograms();
            $master['status_awal'] = $siakad->fetchStatusAwal();
            $master['status_lulus'] = $siakad->fetchGraduationStatus();
        } catch (RuntimeException $e) {
            $master['error'] = $e->getMessage();
        }

        return $master;
    }

    /**
     * @return array<string, string>
     */
    protected function constants(): array
    {
        return [
            'id_perguruan_tinggi' => (string) config('feeder_maps.id_perguruan_tinggi'),
            'id_perguruan_tinggi_pindahan' => (string) config('feeder_maps.id_perguruan_tinggi_pindahan'),
            'id_perguruan_tinggi_rpl' => (string) config('feeder_maps.id_perguruan_tinggi_rpl'),
            'id_jalur_daftar' => (string) config('feeder_maps.id_jalur_daftar'),
            'id_pembiayaan' => (string) config('feeder_maps.id_pembiayaan'),
            'biaya_masuk' => (string) config('feeder_maps.biaya_masuk'),
            'id_status_mahasiswa (aktivitas kuliah)' => (string) config('feeder_maps.perkuliahan.id_status_mahasiswa'),
            'id_jenis_evaluasi (kelas)' => (string) config('feeder_maps.kelas.id_jenis_evaluasi'),
        ];
    }
}

<?php

namespace App\Services;

use App\Support\Siakad\SiakadConfig;
use App\Support\Siakad\SiakadResource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SiakadApiService
{
    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchMahasiswaSync(array $query = []): array
    {
        return $this->fetchList(SiakadResource::MAHASISWA_SYNC, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchPrograms(array $query = []): array
    {
        return $this->fetchList(SiakadResource::PROGRAMS, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchStudyPrograms(array $query = []): array
    {
        return $this->fetchList(SiakadResource::STUDY_PROGRAMS, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchStatusAwal(array $query = []): array
    {
        return $this->fetchList(SiakadResource::STATUS_AWAL, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchAcademicYears(array $query = []): array
    {
        return $this->fetchList(SiakadResource::ACADEMIC_YEARS, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchClasses(array $query = []): array
    {
        return $this->fetchList(SiakadResource::CLASSES, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchClassParticipants(array $query = []): array
    {
        return $this->fetchList(SiakadResource::CLASS_PARTICIPANTS, $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchGrades(array $query = []): array
    {
        return $this->fetchList(SiakadResource::GRADES, $query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchLecturers(array $query = []): array
    {
        return $this->fetchList(SiakadResource::LECTURERS, $query);
    }

    /**
     * KHS per semester — aktivitas kuliah (IPS, IPK, SKS).
     *
     * @param  array<string, scalar|null>  $query  tahun_id wajib; program_id & prodi_id opsional
     * @return list<array<string, mixed>>
     */
    public function fetchKhs(array $query = []): array
    {
        if (trim((string) ($query['tahun_id'] ?? '')) === '') {
            throw new RuntimeException('Parameter tahun_id wajib untuk mengambil data KHS.');
        }

        return $this->fetchList(SiakadResource::KHS, $query);
    }

    /**
     * Daftar angkatan (4 digit tahun masuk).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchCohorts(array $query = []): array
    {
        return $this->fetchList(SiakadResource::COHORTS, $query);
    }

    /**
     * KRS konversi / pindahan / RPL untuk InsertNilaiTransferPendidikanMahasiswa.
     *
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchConversionGrades(array $query = []): array
    {
        return $this->fetchList(SiakadResource::CONVERSION_GRADES, $query);
    }

    /**
     * Mahasiswa lulus / DO (tabel ta).
     *
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchStudentExit(array $query = []): array
    {
        return $this->fetchList(SiakadResource::STUDENT_EXIT, $query);
    }

    /**
     * Master status lulus / DO.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchGraduationStatus(array $query = []): array
    {
        return $this->fetchList(SiakadResource::GRADUATION_STATUS, $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function pingHealth(): array
    {
        return $this->requestGet(
            SiakadConfig::endpointPath(SiakadResource::HEALTH),
            [],
            withToken: false,
        );
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function fetchList(string $resourceKey, array $query = []): array
    {
        $json = $this->requestGet(SiakadConfig::endpointPath($resourceKey), $query);

        return $this->extractDataRows($json);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function requestGet(string $endpoint, array $query = [], bool $withToken = true): array
    {
        if (SiakadConfig::baseUrl() === '') {
            throw new RuntimeException(
                'SIAKAD_API_BASE_URL belum valid. Contoh: http://98.142.245.18/siakad-api/public',
            );
        }

        try {
            $response = $this->httpClient($withToken)->get($endpoint, $query);
            $response->throw();

            $json = $response->json();

            if (! is_array($json)) {
                throw new RuntimeException('Respons Siakad-API bukan JSON valid.');
            }

            return $json;
        } catch (ConnectionException $e) {
            Log::error('Siakad-API connection failed.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Koneksi ke Siakad-API gagal (timeout atau jaringan).',
                0,
                $e,
            );
        } catch (RequestException $e) {
            $rawBody = (string) $e->response?->body();
            $body = $e->response?->json();
            $message = is_array($body)
                ? (string) ($body['message'] ?? $e->getMessage())
                : $e->getMessage();

            if (str_contains($rawBody, '<html') || str_contains($rawBody, '<!DOCTYPE')) {
                $status = (int) ($e->response?->status() ?? 0);
                $message = "HTTP {$status} — URL Siakad-API salah. Set: http://98.142.245.18/siakad-api/public (bukan path folder C:\\...)";
            }

            Log::error('Siakad-API request failed.', [
                'endpoint' => $endpoint,
                'base_url' => SiakadConfig::baseUrl(),
                'status' => $e->response?->status(),
                'message' => $message,
            ]);

            throw new RuntimeException(
                'Siakad-API error: '.$message,
                0,
                $e,
            );
        }
    }

    protected function httpClient(bool $withToken = true)
    {
        $client = Http::timeout((int) config('siakad.timeout', 120))
            ->acceptJson()
            ->baseUrl(SiakadConfig::baseUrl());

        if ($withToken && SiakadConfig::token() !== '') {
            $client = $client->withToken(SiakadConfig::token());
        }

        $apiHost = trim((string) config('siakad.api_host', ''));
        if ($apiHost !== '' && str_contains(SiakadConfig::baseUrl(), '127.0.0.1')) {
            $client = $client->withHeaders(['Host' => $apiHost]);
        }

        return $client;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<array<string, mixed>>
     */
    protected function extractDataRows(array $json): array
    {
        if (($json['success'] ?? true) === false) {
            throw new RuntimeException(
                (string) ($json['message'] ?? 'Siakad-API mengembalikan success=false.'),
            );
        }

        if (isset($json['data']) && is_array($json['data'])) {
            return $this->normalizeDataRows($json['data']);
        }

        if (array_is_list($json)) {
            return $this->normalizeDataRows($json);
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function normalizeDataRows(mixed $data): array
    {
        if (! is_array($data) || $data === []) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        return [];
    }
}

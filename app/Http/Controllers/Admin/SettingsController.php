<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateIntegrationSettingsRequest;
use App\Services\Feeder\FeederClient;
use App\Services\IntegrationSettingsService;
use App\Services\SiakadApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class SettingsController extends Controller
{
    public function index(IntegrationSettingsService $settings): View
    {
        $settings->seedMissingFromEnv();

        return view('admin.settings.index', [
            'title' => 'Pengaturan Koneksi',
            'values' => $settings->forForm(),
            'definitions' => $settings->definitions(),
            'secretSet' => collect($settings->definitions())
                ->filter(fn (array $def, string $key) => ($def['secret'] ?? false) && trim((string) $settings->get($key)) !== '')
                ->mapWithKeys(fn (array $def, string $key) => [$key => true])
                ->all(),
        ]);
    }

    public function update(UpdateIntegrationSettingsRequest $request, IntegrationSettingsService $settings): RedirectResponse
    {
        $settings->updateFromInput($request->validated());
        $settings->applyToConfig();

        return redirect()
            ->route('admin.settings.index', ['tab' => $request->input('tab', 'siakad')])
            ->with('success', 'Pengaturan koneksi berhasil disimpan.');
    }

    public function testSiakad(SiakadApiService $siakad): RedirectResponse
    {
        try {
            $health = $siakad->pingHealth();
            $ok = (bool) ($health['ok'] ?? false);
            $message = $ok
                ? 'Siakad-API terhubung ('.config('siakad.base_url').').'
                : 'Siakad-API merespons tetapi status tidak OK.';
        } catch (RuntimeException $e) {
            return back()->with('test_error', 'Siakad-API: '.$e->getMessage());
        }

        return back()->with('test_success', $message);
    }

    public function testFeeder(FeederClient $feeder): RedirectResponse
    {
        try {
            $feeder->token();
            $message = 'Neo Feeder token OK ('.config('feeder.ws_url').').';
        } catch (RuntimeException $e) {
            return back()->with('test_error', 'Neo Feeder: '.$e->getMessage());
        }

        return back()->with('test_success', $message);
    }
}

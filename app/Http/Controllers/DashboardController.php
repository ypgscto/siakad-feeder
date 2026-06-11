<?php

namespace App\Http\Controllers;

use App\Services\Feeder\FeederClient;
use App\Services\SiakadApiService;
use Illuminate\View\View;
use RuntimeException;

class DashboardController extends Controller
{
    public function __invoke(SiakadApiService $siakad, FeederClient $feeder): View
    {
        $siakadStatus = ['ok' => false, 'message' => 'Belum dikonfigurasi'];
        $feederStatus = ['ok' => false, 'message' => 'Belum dikonfigurasi'];

        try {
            $health = $siakad->pingHealth();
            $siakadStatus = [
                'ok' => (bool) ($health['ok'] ?? false),
                'message' => ($health['ok'] ?? false) ? 'Siakad-API terhubung' : 'Siakad-API tidak siap',
            ];
        } catch (RuntimeException $e) {
            $base = (string) config('siakad.base_url', '');
            $message = $e->getMessage();
            if ($base !== '' && ! str_contains($message, $base)) {
                $message .= ' [URL aktif: '.$base.']';
            }
            $siakadStatus = ['ok' => false, 'message' => $message];
        }

        try {
            $feeder->ping();
            $feederStatus = ['ok' => true, 'message' => 'Neo Feeder token OK'];
        } catch (RuntimeException $e) {
            $feederStatus = ['ok' => false, 'message' => $e->getMessage()];
        }

        return view('dashboard', [
            'title' => 'Dashboard',
            'siakadStatus' => $siakadStatus,
            'feederStatus' => $feederStatus,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Feeder\FeederClient;
use Illuminate\View\View;
use RuntimeException;

class ProfilPtController extends Controller
{
    public function index(FeederClient $feeder): View
    {
        $profil = null;
        $error = null;

        try {
            $idPt = config('feeder.id_perguruan_tinggi');
            $filter = $idPt !== ''
                ? "id_perguruan_tinggi = '{$idPt}'"
                : '';

            $response = $feeder->callJson('GetProfilPT', array_filter([
                'filter' => $filter,
                'order' => '',
                'limit' => 1,
                'offset' => 0,
            ]));

            $profil = $response['data'][0] ?? null;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        return view('admin.profil-pt.index', [
            'title' => 'Profil Perguruan Tinggi',
            'profil' => $profil,
            'error' => $error,
        ]);
    }
}

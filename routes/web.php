<?php

use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\FeederMappingController;
use App\Http\Controllers\Admin\KonversiNilaiController;
use App\Http\Controllers\Admin\MahasiswaKeluarController;
use App\Http\Controllers\Admin\PerkuliahanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\NilaiController;
use App\Http\Controllers\Admin\ProfilPtController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SyncLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/profil-pt', [ProfilPtController::class, 'index'])->name('profil-pt.index');
        Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
        Route::get('/mahasiswa/log', [SyncLogController::class, 'index'])->defaults('module', 'mahasiswa')->name('mahasiswa.log');
        Route::post('/mahasiswa/kirim', [MahasiswaController::class, 'sendFull'])->name('mahasiswa.send-full');
        Route::post('/mahasiswa/kirim-riwayat', [MahasiswaController::class, 'sendRiwayat'])->name('mahasiswa.send-riwayat');
        Route::post('/mahasiswa/update-riwayat', [MahasiswaController::class, 'updateRiwayat'])->name('mahasiswa.update-riwayat');

        Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
        Route::get('/kelas/log', [SyncLogController::class, 'index'])->defaults('module', 'kelas')->name('kelas.log');
        Route::get('/kelas/peserta', [KelasController::class, 'peserta'])->name('kelas.peserta');
        Route::post('/kelas/kirim', [KelasController::class, 'sendKelas'])->name('kelas.send-kelas');
        Route::post('/kelas/kirim-semua', [KelasController::class, 'sendKelasFull'])->name('kelas.send-kelas-full');
        Route::post('/kelas/kirim-peserta', [KelasController::class, 'sendPeserta'])->name('kelas.send-peserta');

        Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai.index');
        Route::get('/nilai/log', [SyncLogController::class, 'index'])->defaults('module', 'nilai')->name('nilai.log');
        Route::get('/nilai/peserta', [NilaiController::class, 'peserta'])->name('nilai.peserta');
        Route::post('/nilai/kirim', [NilaiController::class, 'send'])->name('nilai.send');

        Route::get('/dosen', [DosenController::class, 'index'])->name('dosen.index');

        Route::get('/perkuliahan', [PerkuliahanController::class, 'index'])->name('perkuliahan.index');
        Route::get('/perkuliahan/log', [SyncLogController::class, 'index'])->defaults('module', 'perkuliahan')->name('perkuliahan.log');
        Route::post('/perkuliahan/kirim', [PerkuliahanController::class, 'send'])->name('perkuliahan.send');

        Route::get('/konversi-nilai', [KonversiNilaiController::class, 'index'])->name('konversi-nilai.index');
        Route::get('/konversi-nilai/log', [SyncLogController::class, 'index'])->defaults('module', 'konversi-nilai')->name('konversi-nilai.log');
        Route::get('/konversi-nilai/matakuliah', [KonversiNilaiController::class, 'matakuliah'])->name('konversi-nilai.matakuliah');
        Route::post('/konversi-nilai/kirim', [KonversiNilaiController::class, 'send'])->name('konversi-nilai.send');

        Route::get('/mahasiswa-keluar', [MahasiswaKeluarController::class, 'index'])->name('mahasiswa-keluar.index');
        Route::get('/mahasiswa-keluar/log', [SyncLogController::class, 'index'])->defaults('module', 'mahasiswa-keluar')->name('mahasiswa-keluar.log');
        Route::post('/mahasiswa-keluar/kirim', [MahasiswaKeluarController::class, 'send'])->name('mahasiswa-keluar.send');

        Route::middleware('superadmin')->group(function (): void {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users/lookup', [UserManagementController::class, 'lookup'])->name('users.lookup');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');

            Route::get('/mapping', [FeederMappingController::class, 'index'])->name('mapping.index');
            Route::post('/mapping/prodi', [FeederMappingController::class, 'storeProdi'])->name('mapping.prodi.store');
            Route::delete('/mapping/prodi/{prodiMap}', [FeederMappingController::class, 'destroyProdi'])->name('mapping.prodi.destroy');
            Route::post('/mapping/code', [FeederMappingController::class, 'storeCodeMap'])->name('mapping.code.store');
            Route::delete('/mapping/code/{codeMap}', [FeederMappingController::class, 'destroyCodeMap'])->name('mapping.code.destroy');
            Route::post('/mapping/sync-siakad', [FeederMappingController::class, 'syncFromSiakad'])->name('mapping.sync-siakad');

            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-siakad', [SettingsController::class, 'testSiakad'])->name('settings.test-siakad');
            Route::post('/settings/test-feeder', [SettingsController::class, 'testFeeder'])->name('settings.test-feeder');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

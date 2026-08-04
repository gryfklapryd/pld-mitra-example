<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\IntegrationLogController;
use App\Http\Controllers\Admin\PublishController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicApplicationController;
use App\Http\Controllers\SsoLandingController;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------------
| Sisi member
|-------------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('beranda');

// `Redirect URL` yang didaftarkan di PLD: {APP_URL}/sso?pld_auth=
Route::get('/sso', SsoLandingController::class)->name('sso.landing');

// `detailUrl` tiap item tracking menunjuk ke sini. Kontrak mewajibkannya URL
// absolut yang dapat dibuka member — jadi ia harus benar-benar ada, bukan
// placeholder. Tautan mati di portal PLD tidak menghasilkan galat yang bisa kita
// lihat; hanya member yang menemukannya.
Route::get('/permohonan/{externalRef}', PublicApplicationController::class)
    ->name('permohonan.show');

/*
|-------------------------------------------------------------------------------
| Panel operator
|-------------------------------------------------------------------------------
|
| Tanpa autentikasi — ini aplikasi contoh yang dijalankan lokal. Di aplikasi
| sungguhan, seluruh grup ini WAJIB berada di balik middleware `auth` dan
| otorisasi peran; ia bisa mengubah status permohonan siapa pun.
|
*/

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [ApplicationController::class, 'index'])->name('applications.index');

    Route::get('/permohonan/baru', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/permohonan', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/permohonan/{application}', [ApplicationController::class, 'show'])->name('applications.show');

    Route::post('/permohonan/{application}/naikkan-tahap', [ApplicationController::class, 'advance'])
        ->name('applications.advance');
    Route::post('/permohonan/{application}/kategori', [ApplicationController::class, 'changeCategory'])
        ->name('applications.category');

    Route::get('/log-integrasi', [IntegrationLogController::class, 'index'])->name('logs.index');

    Route::get('/publish', [PublishController::class, 'create'])->name('publish.create');
    Route::post('/publish', [PublishController::class, 'store'])->name('publish.store');
});

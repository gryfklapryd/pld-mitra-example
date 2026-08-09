<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\HubnetClientController;
use App\Http\Controllers\Admin\HubnetUserController;
use App\Http\Controllers\Admin\IntegrationLogController;
use App\Http\Controllers\Admin\PublishController;
use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\Auth\OperatorLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Hubnet\AuthorizeController;
use App\Http\Controllers\PublicApplicationController;
use App\Http\Controllers\SsoLandingController;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------------
| Sisi member
|-------------------------------------------------------------------------------
|
| Member punya DUA pintu masuk, dan keduanya memang harus ada:
|   1. SSO dari portal PLD (tanpa mengetik apa pun)
|   2. Login langsung dengan user_login + password
|
| Pintu kedua bukan pelengkap — tanpanya `API User Validation URL` kehilangan
| maknanya, karena endpoint itu ada justru untuk memverifikasi kredensial yang
| member ketikkan sendiri.
|
*/

Route::get('/', HomeController::class)->name('beranda');

Route::middleware('guest:member')->group(function (): void {
    Route::get('/masuk', [MemberLoginController::class, 'create'])->name('masuk');
    Route::post('/masuk', [MemberLoginController::class, 'store']);
});

Route::post('/keluar', [MemberLoginController::class, 'destroy'])
    ->middleware('auth:member')
    ->name('keluar');

// `Redirect URL` yang didaftarkan di PLD: {APP_URL}/sso?pld_auth=
// Tidak dijaga middleware auth — justru tugasnya MEMBENTUK sesi.
Route::get('/sso', SsoLandingController::class)->name('sso.landing');

// Tujuan `detailUrl` tiap item tracking. Otorisasinya di ApplicationPolicy:
// pemilik proses, atau operator internal. Sebelumnya terbuka bagi siapa pun yang
// menebak nomor permohonan.
Route::get('/permohonan/{externalRef}', PublicApplicationController::class)
    ->name('permohonan.show');

/*
|-------------------------------------------------------------------------------
| Hubnet TIRUAN — kaki PERAMBAN dari alur SSO (IdP palsu)
|-------------------------------------------------------------------------------
|
| Path-nya sengaja sama persis dengan Hubnet asli (/sso/oauth/authorize) supaya
| pld-user/backoffice cukup mengganti HUBNET_DOMAIN tanpa perubahan kode. Kaki
| MESIN (token & userinfo) hidup terpisah di routes/hubnet.php — tanpa sesi/CSRF.
|
| GET  menampilkan halaman login; POST memverifikasi akun dummy lalu meredirect
| kembali ke redirect_uri PLD dengan authorization code. Keduanya PUBLIK — memang
| itu peran halaman login SSO — dan dibatasi laju agar tak jadi alat tebak kredensial.
|
*/
Route::prefix('sso/oauth')
    ->name('hubnet.authorize.')
    ->middleware('throttle:60,1')
    ->group(function (): void {
        Route::get('/authorize', [AuthorizeController::class, 'show'])->name('show');
        Route::post('/authorize', [AuthorizeController::class, 'login'])->name('login');
    });

/*
|-------------------------------------------------------------------------------
| Operator internal
|-------------------------------------------------------------------------------
*/

Route::middleware('guest:web')->group(function (): void {
    Route::get('/operator/masuk', [OperatorLoginController::class, 'create'])->name('operator.masuk');
    Route::post('/operator/masuk', [OperatorLoginController::class, 'store']);
});

Route::post('/operator/keluar', [OperatorLoginController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('operator.keluar');

/*
|-------------------------------------------------------------------------------
| Panel operator — SELURUHNYA di balik autentikasi
|-------------------------------------------------------------------------------
|
| Panel ini bisa mengubah kategori dan tahap permohonan siapa pun, dan tiap
| perubahan menerbitkan notifikasi — sebagian lewat EMAIL — kepada member atas
| nama layanan ini. Terbuka tanpa autentikasi, ia menjadi alat mengirim email
| berkop resmi bagi siapa saja yang menemukan alamatnya.
|
*/

Route::prefix('admin')->name('admin.')->middleware('auth:web')->group(function (): void {
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

    // Pengelolaan Hubnet TIRUAN: identitas dummy + klien OAuth yang boleh SSO.
    Route::resource('hubnet-users', HubnetUserController::class)->except('show');
    Route::resource('hubnet-clients', HubnetClientController::class)->except('show');
    Route::post('/hubnet-clients/{hubnet_client}/regenerate-secret', [HubnetClientController::class, 'regenerateSecret'])
        ->name('hubnet-clients.regenerate');
});

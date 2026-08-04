<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserValidationController;
use App\Http\Middleware\VerifyPldApiKey;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------------
| Endpoint kontrak PLD — ARAH MASUK (PLD memanggil aplikasi ini)
|-------------------------------------------------------------------------------
|
| Ketiganya POST, ketiganya dijaga header `Api-Key` yang sama. Alamatnya bebas —
| yang mengikat adalah nilai yang didaftarkan di form Aplikasi milik PLD:
|
|   API Auth URL            → POST {APP_URL}/api/pld/auth
|   API User Validation URL → POST {APP_URL}/api/pld/user/validation
|   API Tracking URL        → POST {APP_URL}/api/pld/tracking
|
| Tidak ada CSRF di sini (rute api), dan tidak ada sesi. Pemanggilnya mesin.
|
| Batas laju sengaja LONGGAR (120/menit). Ia menahan penyalahgunaan bila alamat
| ini ditemukan orang, tetapi tidak boleh sampai menolak PLD: satu siklus
| sinkronisasi memanggil endpoint tracking sekali per layanan, dan endpoint auth
| sekali tiap member menekan tombol SSO — lonjakan wajar saat jam sibuk tidak
| boleh terbaca sebagai serangan. Menolak PLD justru merugikan: jawaban non-200
| membuat PLD menandai sinkronisasi gagal untuk seluruh member sekaligus.
|
| Perlindungan terhadap tebakan kredensial ada pada `Api-Key` itu sendiri
| (hash_equals di VerifyPldApiKey), bukan pada batas laju ini.
|
*/

Route::middleware(['throttle:120,1', VerifyPldApiKey::class])
    ->prefix('pld')
    ->group(function (): void {
        Route::post('/auth', AuthTokenController::class)->name('pld.auth');
        Route::post('/user/validation', UserValidationController::class)->name('pld.user-validation');
        Route::post('/tracking', TrackingController::class)->name('pld.tracking');
    });

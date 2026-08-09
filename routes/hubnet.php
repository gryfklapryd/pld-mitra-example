<?php

declare(strict_types=1);

use App\Http\Controllers\Hubnet\TokenController;
use App\Http\Controllers\Hubnet\UserInfoController;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------------
| Hubnet TIRUAN — kaki MESIN dari alur SSO (dipanggil pld-user)
|-------------------------------------------------------------------------------
|
| Terpisah dari web.php dengan alasan yang sama seperti endpoint kontrak PLD:
| pemanggilnya mesin, jadi TANPA sesi dan TANPA CSRF. Didaftarkan lewat `then` di
| bootstrap/app.php dengan grup middleware `api` — sehingga TIDAK berprefiks /api
| dan path-nya persis Hubnet asli:
|
|   POST /sso/oauth/token   → tukar authorization code menjadi access token
|   GET  /sso/api/user      → access token (Bearer) menjadi payload data_user
|
| Batas laju longgar (120/menit): satu login menekan token sekali dan userinfo
| sekali, jadi lonjakan wajar tak boleh terbaca sebagai serangan.
|
*/
Route::middleware('throttle:120,1')->group(function (): void {
    Route::post('/sso/oauth/token', TokenController::class)->name('hubnet.token');
    Route::get('/sso/api/user', UserInfoController::class)->name('hubnet.userinfo');
});

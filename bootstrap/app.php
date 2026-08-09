<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Rute kontrak PLD hidup terpisah dari rute web: tanpa sesi, tanpa CSRF,
        // dan pemanggilnya mesin. Mencampurnya ke web.php berarti tiap panggilan
        // PLD ikut membuka sesi yang tak pernah dipakai — dan ikut kena CSRF.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Kaki MESIN Hubnet TIRUAN (token & userinfo). Grup `api` = tanpa sesi/CSRF,
        // dan didaftarkan di sini supaya BEBAS prefiks /api — path-nya harus persis
        // Hubnet asli (/sso/oauth/token, /sso/api/user).
        then: function (): void {
            Route::middleware('api')->group(base_path('routes/hubnet.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aplikasi ini punya DUA populasi pengguna dengan halaman masuk berbeda:
        // member layanan (`member`) dan operator internal (`web`). Bawaan Laravel
        // mengarahkan seluruh tamu ke route bernama `login`, yang tidak ada di sini
        // — tanpa penyetelan ini, `auth:web` gagal dengan "Route [login] not
        // defined" alih-alih menampilkan halaman masuk.
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('admin', 'admin/*', 'operator/*')
                ? route('operator.masuk')
                : route('masuk'),
        );

        // Arah sebaliknya: pengguna yang sudah masuk lalu membuka halaman login.
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->is('operator/*')
                ? route('admin.applications.index')
                : route('beranda'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

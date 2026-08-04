<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OperatorLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Login operator internal — gerbang seluruh panel `/admin`.
 *
 * Panel itu bisa mengubah kategori dan tahap permohonan SIAPA PUN, dan tiap
 * perubahan menerbitkan notifikasi (sebagian lewat email) ke member yang
 * bersangkutan atas nama layanan ini. Membiarkannya terbuka berarti menyerahkan
 * kemampuan mengirim email berkop resmi kepada siapa saja yang menemukan
 * alamatnya.
 *
 * Memakai guard `web` bawaan (model App\Models\User, tabel `users`) — populasi
 * yang sepenuhnya terpisah dari `member`.
 */
final class OperatorLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.operator-login');
    }

    public function store(OperatorLoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = [
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ];

        if (! auth()->guard('web')->attempt($credentials, (bool) $request->boolean('remember'))) {
            $request->recordFailedAttempt();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok.']);
        }

        $request->clearAttempts();

        // Wajib SETELAH login berhasil: mengganti id sesi menutup session fixation,
        // yaitu penyerang menanamkan id sesi yang ia ketahui lebih dulu lalu ikut
        // terangkat menjadi sesi operator begitu korban berhasil masuk.
        $request->session()->regenerate();

        return redirect()->intended(route('admin.applications.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('operator.masuk')->with('status', 'Anda telah keluar.');
    }
}

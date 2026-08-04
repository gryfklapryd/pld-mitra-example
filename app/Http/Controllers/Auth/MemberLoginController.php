<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\ValidateMemberCredentialsAction;
use App\Actions\ValidationOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MemberLoginRequest;
use App\Repositories\Contracts\MemberRepositoryContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Login member ke aplikasi ini secara langsung.
 *
 * Ada dua pintu masuk bagi member, dan keduanya memang harus ada:
 *
 *   1. **Lewat SSO PLD** (SsoLandingController) — member menekan layanan ini di
 *      portal PLD, tanpa mengetik apa pun.
 *   2. **Langsung di sini** (kelas ini) — member membuka aplikasi ini tanpa
 *      melewati portal.
 *
 * Pintu kedua bukan pelengkap: tanpanya, `API User Validation URL` kehilangan
 * maknanya. Endpoint itu ada justru karena member PUNYA kredensial di aplikasi
 * ini yang bisa ia ketikkan — kredensial yang sama yang diverifikasi di sini.
 */
final class MemberLoginController extends Controller
{
    public function __construct(
        private readonly ValidateMemberCredentialsAction $validateCredentials,
        private readonly MemberRepositoryContract $members,
    ) {}

    public function create(): View
    {
        return view('auth.member-login');
    }

    public function store(MemberLoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $userLogin = (string) $request->validated('user_login');
        $password = (string) $request->validated('password');

        // Pemeriksa yang SAMA dengan yang dipanggil PLD lewat
        // /api/pld/user/validation. Satu sumber kebenaran untuk "apakah pasangan
        // kredensial ini sah" — lihat catatan di MemberLoginRequest.
        $outcome = ($this->validateCredentials)($userLogin, $password);

        if ($outcome !== ValidationOutcome::Valid) {
            $request->recordFailedAttempt();

            // Satu pesan untuk "akun tak ada", "password salah", dan "akun
            // nonaktif". Membedakannya menjadikan halaman ini alat memeriksa
            // username mana yang terdaftar.
            return back()
                ->withInput($request->only('user_login'))
                ->withErrors(['user_login' => 'Nama pengguna atau kata sandi tidak cocok.']);
        }

        $request->clearAttempts();

        $member = $this->members->findByUserLogin($userLogin);

        if ($member === null) {
            // Praktis mustahil (outcome Valid berarti member ditemukan), tetapi
            // dijaga agar kegagalan tak terduga berakhir sebagai pesan login biasa
            // alih-alih galat 500 di halaman yang dibuka publik.
            return back()->withErrors(['user_login' => 'Nama pengguna atau kata sandi tidak cocok.']);
        }

        $request->session()->regenerate();
        auth()->guard('member')->login($member, (bool) $request->boolean('remember'));
        $request->session()->put('login_via', 'form');

        return redirect()->intended(route('beranda'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda')->with('status', 'Anda telah keluar.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RedeemSsoTokenAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Titik pendaratan SSO — `Redirect URL` yang didaftarkan di PLD.
 *
 * PLD meredirect member ke sini dengan token yang tadi kita terbitkan lewat
 * `API Auth URL`, sebagai query string. Bentuk yang didaftarkan biasanya:
 *
 *     https://aplikasi-anda/sso?pld_auth=
 *
 * PLD menempelkan token di ujungnya. Nama parameternya ikut apa yang Anda
 * daftarkan; di sini `pld_auth` supaya cocok dengan contoh dokumen integrasi.
 *
 * Setelah ditukar, token langsung mati (sekali pakai) dan URL-nya dibersihkan
 * lewat redirect — token yang tertinggal di address bar akan ikut terbawa ke
 * riwayat peramban dan header Referer halaman berikutnya.
 */
final class SsoLandingController extends Controller
{
    public function __construct(
        private readonly RedeemSsoTokenAction $redeemToken,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $token = (string) $request->query('pld_auth', '');

        if ($token === '') {
            return redirect()
                ->route('beranda')
                ->with('error', 'Tautan SSO tidak membawa token.');
        }

        $member = ($this->redeemToken)($token);

        if ($member === null) {
            // Satu pesan untuk token salah, kedaluwarsa, dan sudah terpakai.
            // Membedakannya tidak menolong member (tindakannya sama: ulangi dari
            // portal PLD) tapi menolong siapa pun yang sedang menebak-nebak token.
            return redirect()
                ->route('beranda')
                ->with('error', 'Tautan SSO tidak berlaku atau sudah digunakan. Silakan ulangi dari portal PLD.');
        }

        auth()->guard('member')->login($member);
        $request->session()->regenerate();

        return redirect()
            ->route('beranda')
            ->with('status', 'Anda masuk sebagai '.$member->name.' lewat SSO PLD.');
    }
}

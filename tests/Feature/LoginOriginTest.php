<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Beranda harus menyatakan CARA MASUK apa adanya.
 *
 * Regresi yang dijaga di sini pernah benar-benar terjadi dan memakan waktu:
 * panel beranda menulis "Masuk lewat SSO PLD" untuk setiap member yang login,
 * termasuk yang masuk lewat form biasa. Saat menguji integrasi, login langsung
 * karenanya tampak seperti bukti bahwa SSO sudah bekerja — menyembunyikan persis
 * hal yang sedang diuji, dan mengarahkan diagnosis ke tempat yang salah.
 */
final class LoginOriginTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-uji-integrasi-pld';

    #[Test]
    public function login_lewat_form_tidak_diklaim_sebagai_sso(): void
    {
        $this->makeMember();

        $this->post('/masuk', ['user_login' => 'budi', 'password' => 'rahasia123'])
            ->assertRedirect(route('beranda'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk langsung di aplikasi ini')
            ->assertDontSee('Masuk lewat SSO PLD');
    }

    #[Test]
    public function login_lewat_sso_dinyatakan_sebagai_sso(): void
    {
        $member = $this->makeMember();

        $token = $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', ['user_login' => $member->user_login])
            ->assertOk()
            ->json('auth_token');

        $this->get('/sso?pld_auth='.$token)->assertRedirect(route('beranda'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk lewat SSO PLD')
            ->assertDontSee('Masuk langsung di aplikasi ini');
    }

    private function makeMember(): Member
    {
        return Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use App\Models\SsoToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Penukaran token SSO di `Redirect URL`.
 */
final class SsoLandingTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-uji-integrasi-pld';

    #[Test]
    public function token_sah_membentuk_sesi_member(): void
    {
        $member = $this->makeMember();
        $token = $this->issueTokenFor($member->user_login);

        $this->get('/sso?pld_auth='.$token)
            ->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($member, 'member');
    }

    #[Test]
    public function token_hanya_bisa_dipakai_satu_kali(): void
    {
        // Token ini mendarat di URL — tempat yang bocor lewat riwayat peramban,
        // header Referer, dan log proxy. Sekali pakai membuat salinan yang bocor
        // sudah mati saat ditemukan.
        $member = $this->makeMember();
        $token = $this->issueTokenFor($member->user_login);

        $this->get('/sso?pld_auth='.$token);
        $this->assertNotNull(SsoToken::query()->firstOrFail()->used_at, 'Token tidak ditandai terpakai');

        // Sesi hasil penukaran pertama harus dibuang dulu. Tanpa ini, `assertGuest`
        // di bawah hanya mengulang keadaan login yang sah dari langkah sebelumnya,
        // bukan menguji penukaran kedua.
        $this->app['auth']->guard('member')->logout();
        $this->flushSession();

        $this->get('/sso?pld_auth='.$token)
            ->assertRedirect(route('beranda'))
            ->assertSessionHas('error');

        $this->assertGuest('member');
    }

    #[Test]
    public function token_kedaluwarsa_ditolak(): void
    {
        $member = $this->makeMember();
        $token = $this->issueTokenFor($member->user_login);

        SsoToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->get('/sso?pld_auth='.$token)->assertSessionHas('error');
        $this->assertGuest('member');
    }

    #[Test]
    public function token_asing_ditolak(): void
    {
        $this->makeMember();

        $this->get('/sso?pld_auth=token-karangan-sendiri')
            ->assertSessionHas('error');

        $this->assertGuest('member');
    }

    #[Test]
    public function member_yang_dinonaktifkan_setelah_token_terbit_tidak_bisa_masuk(): void
    {
        $member = $this->makeMember();
        $token = $this->issueTokenFor($member->user_login);

        $member->forceFill(['is_active' => false])->save();

        $this->get('/sso?pld_auth='.$token)->assertSessionHas('error');
        $this->assertGuest('member');
    }

    #[Test]
    public function tanpa_parameter_token_tidak_membentuk_sesi(): void
    {
        $this->get('/sso')->assertSessionHas('error');
        $this->assertGuest('member');
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

    private function issueTokenFor(string $userLogin): string
    {
        return $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', ['user_login' => $userLogin])
            ->assertOk()
            ->json('auth_token');
    }
}

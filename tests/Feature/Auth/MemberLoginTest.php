<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MemberLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('member-login:budi|127.0.0.1');

        Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.go.id',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function member_dapat_masuk_dengan_kredensial_yang_sama_yang_diverifikasi_pld(): void
    {
        // Pasangan kredensial ini identik dengan yang diteruskan PLD ke
        // /api/pld/user/validation — memang itu maksudnya, dan keduanya memakai
        // pemeriksa yang sama (ValidateMemberCredentialsAction).
        $this->post('/masuk', [
            'user_login' => 'budi',
            'password' => 'rahasia123',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticated('member');
    }

    #[Test]
    public function password_salah_ditolak(): void
    {
        $this->post('/masuk', [
            'user_login' => 'budi',
            'password' => 'salah',
        ])->assertSessionHasErrors('user_login');

        $this->assertGuest('member');
    }

    #[Test]
    public function pesan_galat_tidak_membedakan_akun_tak_ada_dari_password_salah(): void
    {
        // Membedakannya menjadikan halaman login alat memeriksa username mana yang
        // terdaftar di aplikasi ini.
        $pesan = 'Nama pengguna atau kata sandi tidak cocok.';

        $this->post('/masuk', ['user_login' => 'budi', 'password' => 'salah'])
            ->assertSessionHasErrors(['user_login' => $pesan]);

        $this->post('/masuk', ['user_login' => 'hantu', 'password' => 'apa pun'])
            ->assertSessionHasErrors(['user_login' => $pesan]);
    }

    #[Test]
    public function member_nonaktif_tidak_dapat_masuk(): void
    {
        Member::query()->where('user_login', 'budi')->update(['is_active' => false]);

        $this->post('/masuk', ['user_login' => 'budi', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('user_login');

        $this->assertGuest('member');
    }

    #[Test]
    public function percobaan_berulang_dikunci_sementara(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['user_login' => 'budi', 'password' => 'salah']);
        }

        // Percobaan ke-6 ditolak batas laju, bukan lagi oleh pencocokan password —
        // termasuk bila password yang dipakai kali ini BENAR.
        $this->post('/masuk', ['user_login' => 'budi', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('user_login');

        $this->assertGuest('member');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('user_login'),
        );
    }

    #[Test]
    public function member_dapat_keluar(): void
    {
        $member = Member::query()->firstOrFail();

        $this->actingAs($member, 'member')
            ->post('/keluar')
            ->assertRedirect(route('beranda'));

        $this->assertGuest('member');
    }

    #[Test]
    public function halaman_login_mengarahkan_member_yang_sudah_masuk(): void
    {
        $member = Member::query()->firstOrFail();

        $this->actingAs($member, 'member')
            ->get('/masuk')
            ->assertRedirect(route('beranda'));
    }
}

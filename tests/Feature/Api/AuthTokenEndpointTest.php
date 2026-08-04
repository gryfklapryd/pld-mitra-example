<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\SsoToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `API Auth URL` — kontrak v1.0.1.
 */
final class AuthTokenEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-uji-integrasi-pld';

    #[Test]
    public function menolak_permintaan_tanpa_api_key(): void
    {
        $this->postJson('/api/pld/auth', ['user_login' => 'budi'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Api-Key tidak valid.']);
    }

    #[Test]
    public function menolak_api_key_yang_salah(): void
    {
        $this->withHeader('Api-Key', 'kunci-salah')
            ->postJson('/api/pld/auth', ['user_login' => 'budi'])
            ->assertStatus(400);
    }

    #[Test]
    public function menerbitkan_token_untuk_member_aktif(): void
    {
        $member = Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', ['user_login' => 'budi'])
            ->assertOk()
            ->assertJsonStructure(['auth_token', 'expires_in']);

        $token = $response->json('auth_token');

        // Yang tersimpan HASH-nya, bukan nilai mentah. Kalau suatu saat ini
        // berubah, isi tabel sso_tokens langsung menjadi kunci masuk siap pakai.
        $this->assertDatabaseHas('sso_tokens', [
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $token),
        ]);
        $this->assertDatabaseMissing('sso_tokens', ['token_hash' => $token]);
    }

    #[Test]
    public function mencocokkan_user_login_tanpa_peduli_besar_kecil_huruf(): void
    {
        Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', ['user_login' => 'BUDI'])
            ->assertOk();
    }

    #[Test]
    public function menjawab_400_untuk_user_login_tak_dikenal(): void
    {
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', ['user_login' => 'hantu'])
            ->assertStatus(400)
            ->assertJson(['message' => 'User tidak ditemukan.']);
    }

    #[Test]
    public function menjawab_400_untuk_member_nonaktif(): void
    {
        Member::query()->create([
            'user_login' => 'nonaktif',
            'name' => 'Nonaktif',
            'password' => 'rahasia123',
            'is_active' => false,
        ]);

        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', ['user_login' => 'nonaktif'])
            ->assertStatus(400);

        $this->assertSame(0, SsoToken::query()->count());
    }

    #[Test]
    public function galat_validasi_memakai_bentuk_message_bukan_bentuk_422_laravel(): void
    {
        // Kontrak menetapkan {"message": "..."} dengan status 400. Bentuk 422
        // bawaan Laravel (objek `errors`) hanya akan tampil sebagai galat generik
        // di sisi PLD.
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/auth', [])
            ->assertStatus(400)
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('errors');
    }
}

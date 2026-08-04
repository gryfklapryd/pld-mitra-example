<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\IntegrationLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `API User Validation URL` — kontrak v1.0.1.
 *
 * Pemetaan jawaban di sini menentukan apakah member terkunci atau tidak di sisi
 * PLD, jadi tiap cabangnya diuji satu per satu.
 */
final class UserValidationEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-uji-integrasi-pld';

    protected function setUp(): void
    {
        parent::setUp();

        Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.go.id',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function kredensial_cocok_menjawab_is_valid_true(): void
    {
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'budi',
                'password' => 'rahasia123',
            ])
            ->assertOk()
            ->assertExactJson(['is_valid' => true]);
    }

    #[Test]
    public function password_salah_menjawab_200_dengan_is_valid_false(): void
    {
        // 200, BUKAN 401. PLD menghitung ini sebagai percobaan gagal milik member;
        // status lain akan ditafsirkan sebagai gangguan aplikasi kita dan TIDAK
        // dihitung — yang berarti percobaan brute force tak pernah terbendung.
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'budi',
                'password' => 'salah',
            ])
            ->assertOk()
            ->assertExactJson(['is_valid' => false]);
    }

    #[Test]
    public function user_login_tak_dikenal_menjawab_400(): void
    {
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'hantu',
                'password' => 'apa-saja',
            ])
            ->assertStatus(400)
            ->assertJson(['message' => 'User tidak ditemukan.']);
    }

    #[Test]
    public function member_nonaktif_menjawab_is_valid_false_bukan_400(): void
    {
        Member::query()->create([
            'user_login' => 'nonaktif',
            'name' => 'Nonaktif',
            'password' => 'rahasia123',
            'is_active' => false,
        ]);

        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'nonaktif',
                'password' => 'rahasia123',
            ])
            ->assertOk()
            ->assertExactJson(['is_valid' => false]);
    }

    #[Test]
    public function password_tidak_pernah_tersimpan_apa_adanya_di_log(): void
    {
        // Kalau uji ini gugur, basis data aplikasi ini berisi password bersih milik
        // pengguna sistem lain. Ini uji yang paling tidak boleh dihapus.
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'budi',
                'password' => 'rahasia123',
            ])
            ->assertOk();

        $log = IntegrationLog::query()->latest('id')->firstOrFail();

        $this->assertSame('********', $log->request_payload['password']);
        $this->assertStringNotContainsString(
            'rahasia123',
            json_encode($log->request_payload, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function mencocokkan_user_login_tanpa_peduli_besar_kecil_huruf(): void
    {
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'BuDi',
                'password' => 'rahasia123',
            ])
            ->assertOk()
            ->assertExactJson(['is_valid' => true]);
    }
}

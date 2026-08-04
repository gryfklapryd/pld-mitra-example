<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Panel `/admin` bisa mengubah kategori dan tahap permohonan siapa pun, dan tiap
 * perubahan menerbitkan notifikasi — sebagian lewat EMAIL — atas nama layanan ini.
 * Uji di berkas ini menjaga agar ia tidak pernah kembali terbuka.
 */
final class OperatorAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string}>
     */
    public static function halamanAdmin(): array
    {
        return [
            ['/admin'],
            ['/admin/permohonan/baru'],
            ['/admin/log-integrasi'],
            ['/admin/publish'],
        ];
    }

    #[Test]
    #[DataProvider('halamanAdmin')]
    public function tamu_diarahkan_ke_login_operator(string $path): void
    {
        $this->get($path)->assertRedirect(route('operator.masuk'));
    }

    #[Test]
    #[DataProvider('halamanAdmin')]
    public function sesi_member_tidak_membuka_panel_operator(string $path): void
    {
        // Dua populasi terpisah. Member yang masuk lewat SSO tidak boleh diam-diam
        // ikut mendapat kewenangan mengubah permohonan orang lain.
        $member = Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $this->actingAs($member, 'member')
            ->get($path)
            ->assertRedirect(route('operator.masuk'));
    }

    #[Test]
    #[DataProvider('halamanAdmin')]
    public function operator_dapat_membuka_panel(string $path): void
    {
        $operator = User::query()->create([
            'name' => 'Operator',
            'email' => 'operator@pel.test',
            'password' => 'rahasia123',
        ]);

        $this->actingAs($operator, 'web')->get($path)->assertOk();
    }

    #[Test]
    public function operator_dapat_masuk_dan_keluar(): void
    {
        RateLimiter::clear('operator-login:operator@pel.test|127.0.0.1');

        User::query()->create([
            'name' => 'Operator',
            'email' => 'operator@pel.test',
            'password' => 'rahasia123',
        ]);

        $this->post('/operator/masuk', [
            'email' => 'operator@pel.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.applications.index'));

        $this->assertAuthenticated('web');

        $this->post('/operator/keluar')->assertRedirect(route('operator.masuk'));
        $this->assertGuest('web');
    }

    #[Test]
    public function kredensial_operator_yang_salah_ditolak(): void
    {
        RateLimiter::clear('operator-login:operator@pel.test|127.0.0.1');

        User::query()->create([
            'name' => 'Operator',
            'email' => 'operator@pel.test',
            'password' => 'rahasia123',
        ]);

        $this->post('/operator/masuk', [
            'email' => 'operator@pel.test',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    #[Test]
    public function endpoint_kontrak_pld_tidak_ikut_terkunci(): void
    {
        // Regresi yang paling mahal bila terjadi: menambahkan autentikasi web lalu
        // tanpa sadar ikut menutup jalur mesin. PLD akan menandai sinkronisasi
        // gagal untuk SELURUH member sekaligus, dan tak ada seorang pun yang
        // melihat galatnya di sisi ini.
        Member::query()->create([
            'user_login' => 'budi',
            'name' => 'Budi',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $this->withHeader('Api-Key', 'kunci-uji-integrasi-pld')
            ->postJson('/api/pld/tracking', [
                'contractVersion' => '1.0',
                'userLogins' => ['budi'],
            ])
            ->assertOk();

        $this->withHeader('Api-Key', 'kunci-uji-integrasi-pld')
            ->postJson('/api/pld/auth', ['user_login' => 'budi'])
            ->assertOk();

        $this->withHeader('Api-Key', 'kunci-uji-integrasi-pld')
            ->postJson('/api/pld/user/validation', [
                'user_login' => 'budi',
                'password' => 'rahasia123',
            ])
            ->assertOk();
    }
}

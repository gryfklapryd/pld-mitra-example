<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kepemilikan halaman tujuan `detailUrl`.
 *
 * URL ini kita kirimkan sendiri ke PLD dan muncul sebagai tombol "Buka di
 * layanan" di portal member. `external_ref` berbentuk berurutan dan tercetak di
 * surat, jadi "harus tahu nomornya" bukan penghalang — satu nomor cukup untuk
 * menelusuri seluruh permohonan setahun bila halaman ini tidak dijaga.
 */
final class ApplicationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tamu_diarahkan_ke_login_member(): void
    {
        $application = $this->makeApplicationFor('budi');

        // Bukan 403/404: member yang menekan tombol dari portal PLD harus mendarat
        // di halaman masuk, lalu dikembalikan ke sini — tautan dari PLD tidak boleh
        // berakhir sebagai layar galat.
        $this->get('/permohonan/'.$application->external_ref)
            ->assertRedirect(route('masuk'));
    }

    #[Test]
    public function pemilik_dapat_membuka_permohonannya(): void
    {
        $application = $this->makeApplicationFor('budi');

        $this->actingAs($application->member, 'member')
            ->get('/permohonan/'.$application->external_ref)
            ->assertOk()
            ->assertSee($application->label);
    }

    #[Test]
    public function member_lain_menerima_404_bukan_403(): void
    {
        $milikBudi = $this->makeApplicationFor('budi');
        $siti = Member::query()->create([
            'user_login' => 'siti',
            'name' => 'Siti',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        // 404, bukan 403. Menjawab "ada tapi bukan milikmu" tetap membocorkan
        // keberadaan nomor permohonan itu kepada siapa pun yang menebak.
        $this->actingAs($siti, 'member')
            ->get('/permohonan/'.$milikBudi->external_ref)
            ->assertNotFound();
    }

    #[Test]
    public function operator_dapat_membuka_permohonan_siapa_pun(): void
    {
        $application = $this->makeApplicationFor('budi');
        $operator = User::query()->create([
            'name' => 'Operator',
            'email' => 'operator@pel.test',
            'password' => 'rahasia123',
        ]);

        $this->actingAs($operator, 'web')
            ->get('/permohonan/'.$application->external_ref)
            ->assertOk()
            ->assertSee('sebagai <strong>operator</strong>', false);
    }

    #[Test]
    public function external_ref_tak_dikenal_menjawab_404(): void
    {
        $application = $this->makeApplicationFor('budi');

        $this->actingAs($application->member, 'member')
            ->get('/permohonan/PRZ-9999-99999')
            ->assertNotFound();
    }

    private function makeApplicationFor(string $userLogin): Application
    {
        $member = Member::query()->create([
            'user_login' => $userLogin,
            'name' => ucfirst($userLogin),
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        return Application::factory()
            ->withStages(3, 2)
            ->for($member)
            ->create(['external_ref' => 'PRZ-2026-00123']);
    }
}

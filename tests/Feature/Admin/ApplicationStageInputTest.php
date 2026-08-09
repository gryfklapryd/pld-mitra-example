<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Masukan tahap pada form buat-permohonan: baris dinamis + batas kontrak.
 *
 * Menjaga dua sifat yang tak terlihat di layar: baris tahap KOSONG disaring
 * sebelum validasi (jadi baris dinamis yang ditambah lalu dibiarkan kosong tak
 * menggagalkan pengiriman), dan jumlah tahap dibatasi pld.limits.timeline_entries.
 */
final class ApplicationStageInputTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function halaman_buat_permohonan_menampilkan_kontrol_tahap_dinamis(): void
    {
        $this->actingAs($this->operator());

        $this->get(route('admin.applications.create'))
            ->assertOk()
            ->assertSee('Tambah tahap');
    }

    #[Test]
    public function baris_tahap_kosong_disaring_bukan_ditolak(): void
    {
        $this->actingAs($this->operator());
        $member = $this->member();

        $this->post(route('admin.applications.store'), [
            'member_id' => $member->id,
            'label' => 'Uji tahap dinamis',
            'stage_names' => ['Permohonan diterima', '', '  ', 'Penerbitan izin'],
        ])->assertRedirect();

        $application = Application::query()->where('member_id', $member->id)->latest('id')->firstOrFail();

        $this->assertSame(2, $application->total_stages);
        $this->assertSame(
            ['Permohonan diterima', 'Penerbitan izin'],
            $application->stages()->orderBy('stage')->pluck('name')->all(),
        );
    }

    #[Test]
    public function semua_tahap_kosong_ditolak_dengan_pesan_minimal_satu(): void
    {
        $this->actingAs($this->operator());
        $member = $this->member();

        $this->post(route('admin.applications.store'), [
            'member_id' => $member->id,
            'label' => 'Tanpa tahap',
            'stage_names' => ['', '   '],
        ])->assertSessionHasErrors('stage_names');
    }

    #[Test]
    public function melebihi_batas_timeline_entries_ditolak(): void
    {
        $this->actingAs($this->operator());
        $member = $this->member();
        $max = (int) config('pld.limits.timeline_entries');

        $this->post(route('admin.applications.store'), [
            'member_id' => $member->id,
            'label' => 'Kebanyakan tahap',
            'stage_names' => array_map(static fn (int $i): string => "Tahap {$i}", range(1, $max + 1)),
        ])->assertSessionHasErrors('stage_names');
    }

    private function operator(): User
    {
        return User::query()->create([
            'name' => 'Operator Uji',
            'email' => 'op-'.bin2hex(random_bytes(4)).'@pel.test',
            'password' => 'rahasia123',
        ]);
    }

    private function member(): Member
    {
        return Member::query()->create([
            'user_login' => 'mbr_'.bin2hex(random_bytes(3)),
            'name' => 'Member Uji',
            'email' => 'm@example.test',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TrackingCategory;
use App\Models\Application;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh yang mencakup KEDUA cabang aturan kontrak yang paling gampang
 * dilanggar, supaya keduanya bisa dilihat bekerja tanpa mengetik apa pun:
 *
 *   - proses BERJALAN (ACTION_REQUIRED) → tepat satu tahap CURRENT, actionRequired terisi
 *   - proses TERMINAL (COMPLETED)       → nol tahap CURRENT, actionRequired null
 *
 * Nama dan angka mengikuti contoh pada dokumen integrasi §9 supaya mudah
 * dicocokkan saat membaca dokumen dan aplikasi berdampingan.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Operator internal — pemegang akses panel /admin. Populasi yang sepenuhnya
        // terpisah dari member layanan.
        User::query()->create([
            'name' => 'Operator PEL',
            'email' => 'operator@pel.test',
            'password' => 'rahasia123',
        ]);

        $john = Member::query()->create([
            'user_login' => 'john_doe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.go.id',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $siti = Member::query()->create([
            'user_login' => 'siti_aminah',
            'name' => 'Siti Aminah',
            'email' => 'siti.aminah@example.go.id',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $this->actionRequiredExample($john);
        $this->completedExample($john);
        $this->inProgressExample($siti);

        // Identitas DUMMY untuk Hubnet TIRUAN (IdP palsu uji SSO). Terpisah dari
        // member aplikasi ini — ia mewakili "basis data Pusdatin", bukan pengguna PEL.
        $this->call(HubnetUserSeeder::class);
        $this->call(HubnetClientSeeder::class);
    }

    /**
     * Proses berjalan yang menunggu tindakan member.
     *
     * Kombinasi dengan aturan terbanyak: kategori ACTION_REQUIRED mewajibkan
     * actionRequired terisi, dan sebagai item berjalan ia wajib punya TEPAT SATU
     * tahap CURRENT.
     */
    private function actionRequiredExample(Member $member): void
    {
        $application = Application::query()->create([
            'external_ref' => 'PRZ-2026-00123',
            'member_id' => $member->id,
            'label' => 'Perpanjangan Izin Usaha Angkutan Udara Niaga',
            'category' => TrackingCategory::ActionRequired,
            'status_label' => 'Dokumen teknis perlu diperbaiki',
            'submitted_at' => Carbon::parse('2026-07-20 08:00:00'),
            'status_changed_at' => Carbon::parse('2026-07-25 10:00:00'),
            'current_stage' => 3,
            'total_stages' => 5,
            'action_instruction' => 'Unggah ulang sertifikat kelaikan yang masih berlaku.',
            'action_due_at' => Carbon::parse('2026-08-05 00:00:00'),
            'action_url' => 'https://pel.example.go.id/permohonan/PRZ-2026-00123/perbaikan',
            'contact_unit' => 'Direktorat Kelaikudaraan dan Pengoperasian Pesawat Udara',
            'contact_email' => 'dkppu@example.go.id',
            'contact_phone' => '021-3506664',
        ]);

        $this->stages($application, [
            ['Permohonan diterima', '2026-07-20 08:00:00', 'Sistem', null],
            ['Verifikasi kelengkapan administrasi', '2026-07-22 04:15:00', 'Subbag Tata Usaha', 'Seluruh dokumen administrasi lengkap.'],
            ['Verifikasi teknis', '2026-07-25 10:00:00', 'Tim Teknis DKPPU', 'Sertifikat kelaikan yang dilampirkan sudah kedaluwarsa.'],
            ['Persetujuan Direktur', null, null, null],
            ['Penerbitan izin', null, null, null],
        ]);

        $application->attributes()->createMany([
            ['label' => 'Nomor Izin Berjalan', 'value' => 'SIUAU-2021-0447'],
            ['label' => 'Jenis Angkutan', 'value' => 'Niaga Berjadwal'],
        ]);
    }

    /**
     * Proses selesai — nol tahap CURRENT, actionRequired null, dan membawa
     * dokumen hasil sebagai TAUTAN (bukan isi berkas).
     */
    private function completedExample(Member $member): void
    {
        $application = Application::query()->create([
            'external_ref' => 'SRT-2026-00088',
            'member_id' => $member->id,
            'label' => 'Sertifikasi Personel Ground Handling',
            'category' => TrackingCategory::Completed,
            'status_label' => 'Sertifikat telah terbit',
            'submitted_at' => Carbon::parse('2026-05-02 03:00:00'),
            'status_changed_at' => Carbon::parse('2026-06-11 07:30:00'),
            'expires_at' => Carbon::parse('2029-06-11 00:00:00'),
            'current_stage' => 4,
            'total_stages' => 4,
            'contact_unit' => 'Subdirektorat Personel Penerbangan',
            'contact_email' => 'personel@example.go.id',
        ]);

        $this->stages($application, [
            ['Pendaftaran peserta', '2026-05-02 03:00:00', 'Sistem', null],
            ['Verifikasi berkas', '2026-05-09 06:00:00', 'Subbag Tata Usaha', null],
            ['Ujian kompetensi', '2026-06-02 02:00:00', 'Tim Penguji', 'Lulus dengan nilai 87.'],
            ['Penerbitan sertifikat', '2026-06-11 07:30:00', 'Subdirektorat Personel Penerbangan', null],
        ]);

        $application->documents()->create([
            'label' => 'Sertifikat Kompetensi Ground Handling',
            'url' => 'https://pel.example.go.id/dokumen/SRT-2026-00088.pdf',
            'issued_at' => Carbon::parse('2026-06-11 07:30:00'),
        ]);

        $application->attributes()->create([
            'label' => 'Nomor Sertifikat',
            'value' => 'GH-2026-00088',
        ]);
    }

    private function inProgressExample(Member $member): void
    {
        $application = Application::query()->create([
            'external_ref' => 'PRZ-2026-00201',
            'member_id' => $member->id,
            'label' => 'Permohonan Izin Rute Penerbangan Perintis',
            'category' => TrackingCategory::InProgress,
            'status_label' => 'Menunggu verifikasi berkas',
            'submitted_at' => Carbon::parse('2026-07-28 02:00:00'),
            'status_changed_at' => Carbon::parse('2026-07-30 08:00:00'),
            'current_stage' => 2,
            'total_stages' => 5,
            'contact_unit' => 'Direktorat Angkutan Udara',
        ]);

        $this->stages($application, [
            ['Permohonan diterima', '2026-07-28 02:00:00', 'Sistem', null],
            ['Verifikasi kelengkapan administrasi', '2026-07-30 08:00:00', 'Subbag Tata Usaha', null],
            ['Kajian rute', null, null, null],
            ['Persetujuan Direktur', null, null, null],
            ['Penerbitan izin', null, null, null],
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string|null, 2: string|null, 3: string|null}>  $rows
     */
    private function stages(Application $application, array $rows): void
    {
        foreach ($rows as $index => [$name, $occurredAt, $actor, $note]) {
            $application->stages()->create([
                'stage' => $index + 1,
                'name' => $name,
                'occurred_at' => $occurredAt === null ? null : Carbon::parse($occurredAt),
                'actor' => $actor,
                'note' => $note,
            ]);
        }
    }
}

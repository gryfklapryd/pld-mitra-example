<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\HubnetUserType;

/**
 * Identitas DUMMY Hubnet TIRUAN — satu sumber kebenaran.
 *
 * Dipakai seeder untuk mengisi tabel `hubnet_users`, dan halaman login untuk
 * menampilkan daftar akun yang bisa dicoba. Menaruhnya di satu tempat mencegah
 * daftar di layar dan isi basis data berbeda pendapat.
 *
 * Angka identitas (NIP/NIK) mengikuti payload contoh yang sudah dipakai uji Go
 * di pld-user (hubnet_identity_test.go) supaya provisioning menghasilkan hasil
 * yang bisa ditelusuri. Semua kata sandi sama dan jelas-jelas dummy.
 */
final class HubnetSeedAccounts
{
    public const PASSWORD = 'hubnet123';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            // 1) Pegawai aktif, unit DJPU → login berhasil sebagai akun PERSON.
            //    kode_unit DJPU juga membuatnya LAYAK jadi admin (K14).
            [
                'type' => HubnetUserType::Pegawai,
                'username' => '199608082022031008',
                'name' => 'MUHAMMAD HAMDANI',
                'email' => 'muhammad_hamdani@kemenhub.go.id',
                'nip' => '199608082022031008',
                'nik' => '3514140808960004',
                'phone' => '081200000001',
                'unit' => 'Direktorat Jenderal Perhubungan Udara',
                'kode_unit' => '005000000000000',
                'golongan' => 'II/d',
                'pangkat' => 'Pengatur Tingkat I',
                'jabatan_fungsional' => 'Pengolah Data dan Informasi',
                'jabatan_struktural' => null,
                'status' => true,
                'is_deleted' => false,
                'note' => 'Pegawai aktif DJPU → berhasil masuk (akun PERSON).',
            ],

            // 2) Pegawai NONAKTIF (status=0) → pld-user menolak dengan B5 (ErrUserInactive).
            [
                'type' => HubnetUserType::Pegawai,
                'username' => '198701012010012002',
                'name' => 'SITI RAHAYU',
                'email' => 'siti_rahayu@kemenhub.go.id',
                'nip' => '198701012010012002',
                'nik' => '3201010101870002',
                'phone' => '081200000002',
                'unit' => 'Direktorat Jenderal Perhubungan Udara',
                'kode_unit' => '005000000000000',
                'golongan' => 'III/b',
                'pangkat' => 'Penata Muda Tingkat I',
                'jabatan_fungsional' => 'Analis Kebijakan',
                'jabatan_struktural' => null,
                'status' => false,
                'is_deleted' => false,
                'note' => 'Pegawai NONAKTIF → ditolak (B5 / identitas nonaktif).',
            ],

            // 3) Badan usaha OSS via NIB → login berhasil sebagai akun ORGANIZATION.
            [
                'type' => HubnetUserType::Oss,
                'username' => '1409210000868',
                'name' => 'PT ANGKASA NUSANTARA',
                'email' => 'legal@angkasanusantara.co.id',
                'nip' => null,
                'nik' => '3172050505900003',
                'npwp' => '094883561402000',
                'phone' => '081200000003',
                'status' => true,
                'is_deleted' => false,
                'note' => 'OSS pakai NIB → berhasil masuk (akun ORGANIZATION).',
            ],

            // 4) OSS via EMAIL → pld-user menolak dengan K4 (payload tak membawa NIB).
            [
                'type' => HubnetUserType::Oss,
                'username' => 'tockhamdani@gmail.com',
                'name' => 'MUHAMMAD HAMDANI',
                'email' => 'tockhamdani@gmail.com',
                'nip' => null,
                'nik' => '3514140808960004',
                'npwp' => '351414080896000',
                'phone' => '081200000004',
                'status' => true,
                'is_deleted' => false,
                'note' => 'OSS pakai EMAIL → ditolak (K4 "harus pakai NIB").',
            ],

            // 5) Pengguna eksternal (type 3) → pld-user menolak (ErrForbidden); tipe 3
            //    belum matang di Pusdatin. Disediakan agar penolakannya bisa dilihat.
            [
                'type' => HubnetUserType::Lainnya,
                'username' => 'warga@gmail.com',
                'name' => 'BUDI WARGA',
                'email' => 'warga@gmail.com',
                'nip' => null,
                'nik' => '3273010101950001',
                'phone' => '081200000005',
                'status' => true,
                'is_deleted' => false,
                'note' => 'Eksternal (type 3) → ditolak (belum didukung PLD).',
            ],
        ];
    }
}

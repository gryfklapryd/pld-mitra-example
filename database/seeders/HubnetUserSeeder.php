<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use App\Support\HubnetSeedAccounts;
use Illuminate\Database\Seeder;

/**
 * Mengisi direktori identitas Hubnet TIRUAN dengan akun DUMMY.
 *
 * Idempoten: dijalankan berulang tidak menggandakan baris (updateOrCreate atas
 * pasangan unik type+username). Dipanggil DatabaseSeeder untuk `migrate --seed`
 * lokal, dan juga oleh perintah `php artisan hubnet:seed` untuk server yang
 * hanya menjalankan `migrate` (deploy tidak pernah menyemai otomatis).
 */
final class HubnetUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HubnetSeedAccounts::all() as $account) {
            /** @var HubnetUserType $type */
            $type = $account['type'];

            HubnetUser::query()->updateOrCreate(
                ['type' => $type->value, 'username' => $account['username']],
                [
                    'password' => HubnetSeedAccounts::PASSWORD,
                    'name' => $account['name'],
                    'email' => $account['email'] ?? null,
                    'nip' => $account['nip'] ?? null,
                    'nik' => $account['nik'] ?? null,
                    'npwp' => $account['npwp'] ?? null,
                    'phone' => $account['phone'] ?? null,
                    'unit' => $account['unit'] ?? null,
                    'kode_unit' => $account['kode_unit'] ?? null,
                    'golongan' => $account['golongan'] ?? null,
                    'pangkat' => $account['pangkat'] ?? null,
                    'jabatan_fungsional' => $account['jabatan_fungsional'] ?? null,
                    'jabatan_struktural' => $account['jabatan_struktural'] ?? null,
                    'status' => $account['status'] ?? true,
                    'is_deleted' => $account['is_deleted'] ?? false,
                ],
            );
        }
    }
}

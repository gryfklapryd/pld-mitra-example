<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HubnetOAuthClient;
use Illuminate\Database\Seeder;

/**
 * Memindahkan klien default (dari config/hubnet.php / env) ke tabel, supaya klien
 * yang selama ini dipakai lewat env juga tampil & bisa dikelola di panel operator.
 *
 * Idempoten. Dilewati bila config tidak memuat client_id — mesin yang belum
 * mengisi env tidak mendapat baris kosong.
 */
final class HubnetClientSeeder extends Seeder
{
    public function run(): void
    {
        $clientId = (string) config('hubnet.client_id');
        $secret = (string) config('hubnet.client_secret');

        if ($clientId === '' || $secret === '') {
            return;
        }

        /** @var array<int, string> $redirectUris */
        $redirectUris = (array) config('hubnet.redirect_uris', []);

        HubnetOAuthClient::query()->updateOrCreate(
            ['client_id' => $clientId],
            [
                'name' => 'PLD (default dari env)',
                'client_secret' => $secret,
                'redirect_uris' => array_values($redirectUris),
                'is_active' => true,
            ],
        );
    }
}

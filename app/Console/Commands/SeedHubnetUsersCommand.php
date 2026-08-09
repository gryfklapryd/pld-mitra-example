<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\HubnetSeedAccounts;
use Database\Seeders\HubnetClientSeeder;
use Database\Seeders\HubnetUserSeeder;
use Illuminate\Console\Command;

/**
 * Menyemai akun DUMMY Hubnet TIRUAN di server.
 *
 * `migrate` tidak menciptakan akun apa pun, dan deploy hanya menjalankan
 * `migrate --force` — jadi tanpa perintah ini server tak punya identitas Hubnet
 * untuk diuji. Aman diulang (idempoten).
 */
final class SeedHubnetUsersCommand extends Command
{
    protected $signature = 'hubnet:seed';

    protected $description = 'Menyemai akun DUMMY + klien default Hubnet TIRUAN (idempoten)';

    public function handle(HubnetUserSeeder $seeder, HubnetClientSeeder $clientSeeder): int
    {
        $seeder->run();
        $clientSeeder->run();

        $this->components->info('Akun & klien default Hubnet TIRUAN tersemai.');
        $this->line('  Kata sandi semua akun: <fg=yellow>'.HubnetSeedAccounts::PASSWORD.'</>');
        $this->line('');

        foreach (HubnetSeedAccounts::all() as $account) {
            $this->line(sprintf(
                '  [%s] <fg=cyan>%s</> — %s',
                $account['type']->radioLabel(),
                $account['username'],
                $account['note'],
            ));
        }

        return self::SUCCESS;
    }
}

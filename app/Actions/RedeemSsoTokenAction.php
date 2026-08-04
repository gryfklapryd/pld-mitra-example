<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Models\SsoToken;
use App\Repositories\Contracts\SsoTokenRepositoryContract;
use Illuminate\Database\ConnectionInterface;

/**
 * Menukar token SSO dari URL redirect menjadi member yang sah.
 *
 * Dijalankan di dalam transaksi dengan penguncian baris: token ini sekali pakai,
 * dan tanpa kunci, dua permintaan yang tiba nyaris bersamaan (peramban yang
 * mengulang, prefetch, dobel klik) sama-sama lolos pemeriksaan `used_at IS NULL`
 * sebelum salah satunya sempat menandainya.
 */
final readonly class RedeemSsoTokenAction
{
    public function __construct(
        private SsoTokenRepositoryContract $tokens,
        private ConnectionInterface $connection,
    ) {}

    public function __invoke(string $token): ?Member
    {
        return $this->connection->transaction(function () use ($token): ?Member {
            $record = $this->tokens->findRedeemableForUpdate(hash('sha256', $token));

            if (! $record instanceof SsoToken) {
                return null;
            }

            $this->tokens->markUsed($record);

            $member = $record->member;

            // Akun yang dinonaktifkan setelah token terbit tidak boleh tetap masuk.
            // Jendelanya memang sempit, tapi "sempit" bukan "tidak ada".
            if (! $member->is_active) {
                return null;
            }

            return $member;
        });
    }
}

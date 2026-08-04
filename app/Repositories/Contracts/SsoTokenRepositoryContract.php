<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Member;
use App\Models\SsoToken;

interface SsoTokenRepositoryContract
{
    public function issue(Member $member, string $tokenHash, int $ttlSeconds): SsoToken;

    /**
     * Ambil token yang masih boleh ditukar, DENGAN penguncian baris.
     *
     * Kuncinya bagian dari kontrak method ini, bukan detail pemanggil: token ini
     * sekali pakai, dan pembacaan tanpa kunci membuat dua permintaan bersamaan
     * sama-sama lolos.
     */
    public function findRedeemableForUpdate(string $tokenHash): ?SsoToken;

    public function markUsed(SsoToken $token): void;

    public function pruneExpiredFor(Member $member): void;
}

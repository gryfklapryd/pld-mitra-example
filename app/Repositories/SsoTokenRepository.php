<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Member;
use App\Models\SsoToken;
use App\Repositories\Contracts\SsoTokenRepositoryContract;

final class SsoTokenRepository implements SsoTokenRepositoryContract
{
    public function issue(Member $member, string $tokenHash, int $ttlSeconds): SsoToken
    {
        return $member->ssoTokens()->create([
            'token_hash' => $tokenHash,
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);
    }

    public function findRedeemableForUpdate(string $tokenHash): ?SsoToken
    {
        return SsoToken::query()
            ->with('member')
            ->where('token_hash', $tokenHash)
            ->redeemable()
            ->lockForUpdate()
            ->first();
    }

    public function markUsed(SsoToken $token): void
    {
        $token->update(['used_at' => now()]);
    }

    public function pruneExpiredFor(Member $member): void
    {
        SsoToken::query()
            ->where('member_id', $member->id)
            ->where('expires_at', '<', now()->subDay())
            ->delete();
    }
}

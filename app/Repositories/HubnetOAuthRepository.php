<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\HubnetAccessToken;
use App\Models\HubnetAuthorizationCode;
use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetOAuthRepositoryContract;

final class HubnetOAuthRepository implements HubnetOAuthRepositoryContract
{
    public function issueCode(HubnetUser $user, string $codeHash, string $clientId, string $redirectUri, int $ttlSeconds): HubnetAuthorizationCode
    {
        return $user->authorizationCodes()->create([
            'code_hash' => $codeHash,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);
    }

    public function findRedeemableCodeForUpdate(string $codeHash): ?HubnetAuthorizationCode
    {
        return HubnetAuthorizationCode::query()
            ->with('hubnetUser')
            ->where('code_hash', $codeHash)
            ->redeemable()
            ->lockForUpdate()
            ->first();
    }

    public function markCodeUsed(HubnetAuthorizationCode $code): void
    {
        $code->update(['used_at' => now()]);
    }

    public function issueToken(HubnetUser $user, string $tokenHash, int $ttlSeconds): HubnetAccessToken
    {
        return $user->accessTokens()->create([
            'token_hash' => $tokenHash,
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);
    }

    public function findValidToken(string $tokenHash): ?HubnetAccessToken
    {
        return HubnetAccessToken::query()
            ->with('hubnetUser')
            ->where('token_hash', $tokenHash)
            ->valid()
            ->first();
    }

    public function pruneExpired(): void
    {
        HubnetAuthorizationCode::query()->where('expires_at', '<', now()->subDay())->delete();
        HubnetAccessToken::query()->where('expires_at', '<', now()->subDay())->delete();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HubnetAuthorizationCode;
use App\Repositories\Contracts\HubnetOAuthRepositoryContract;
use App\Support\OAuthClient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * Menukar authorization code menjadi access token (endpoint /sso/oauth/token).
 *
 * Dijalankan dalam transaksi dengan penguncian baris: code sekali pakai, dan
 * tanpa kunci dua penukaran yang tiba nyaris bersamaan (retry pld-user, dobel)
 * sama-sama lolos pemeriksaan `used_at IS NULL` sebelum salah satunya menandainya.
 *
 * Rahasia klien sudah diverifikasi di FormRequest sebelum sampai ke sini; yang
 * ditegakkan di sini adalah keterikatan code ⇄ klien ⇄ redirect_uri.
 */
final readonly class ExchangeAuthorizationCodeAction
{
    public function __construct(
        private HubnetOAuthRepositoryContract $oauth,
        private ConnectionInterface $connection,
    ) {}

    public function __invoke(string $code, OAuthClient $client, string $redirectUri): TokenExchangeResult
    {
        return $this->connection->transaction(function () use ($code, $client, $redirectUri): TokenExchangeResult {
            $record = $this->oauth->findRedeemableCodeForUpdate(hash('sha256', $code));

            if (! $record instanceof HubnetAuthorizationCode) {
                return TokenExchangeResult::failure('invalid_grant');
            }

            // Code milik klien LAIN, atau redirect_uri berbeda dengan saat terbit:
            // dua pengaman OAuth terhadap code yang dicuri lalu ditukar di tempat lain.
            if (! hash_equals($record->client_id, $client->id) || ! hash_equals($record->redirect_uri, $redirectUri)) {
                return TokenExchangeResult::failure('invalid_grant');
            }

            $this->oauth->markCodeUsed($record);

            $rawToken = Str::random(64);
            $ttl = (int) config('hubnet.token_ttl');
            $this->oauth->issueToken($record->hubnetUser, hash('sha256', $rawToken), $ttl);

            return TokenExchangeResult::success($rawToken, $ttl);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetOAuthRepositoryContract;
use App\Support\OAuthClient;
use Illuminate\Support\Str;

/**
 * Menerbitkan authorization code setelah login berhasil.
 *
 * Mengembalikan nilai MENTAH (untuk ditempel di redirect ke PLD); yang tersimpan
 * hanya hash-nya, jadi isi tabel yang bocor tak langsung bisa ditukar jadi token.
 * `redirect_uri` ikut disimpan supaya penukaran token wajib memakai tujuan yang
 * sama dengan saat code diterbitkan.
 */
final readonly class IssueAuthorizationCodeAction
{
    public function __construct(
        private HubnetOAuthRepositoryContract $oauth,
    ) {}

    public function __invoke(HubnetUser $user, OAuthClient $client, string $redirectUri): string
    {
        $code = Str::random(64);
        $ttl = (int) config('hubnet.code_ttl');

        $this->oauth->issueCode($user, hash('sha256', $code), $client->id, $redirectUri, $ttl);
        $this->oauth->pruneExpired();

        return $code;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HubnetAccessToken;
use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetOAuthRepositoryContract;

/**
 * Menukar access token (dari header Authorization) menjadi identitas Hubnet
 * yang diwakilinya (endpoint /sso/api/user).
 *
 * Menerima nilai Bearer mentah; mencocokkannya lewat hash. Token yang tak dikenal
 * atau kedaluwarsa menghasilkan null — dipetakan controller ke 401.
 */
final readonly class ResolveAccessTokenAction
{
    public function __construct(
        private HubnetOAuthRepositoryContract $oauth,
    ) {}

    public function __invoke(string $rawToken): ?HubnetUser
    {
        if ($rawToken === '') {
            return null;
        }

        $record = $this->oauth->findValidToken(hash('sha256', $rawToken));

        if (! $record instanceof HubnetAccessToken) {
            return null;
        }

        return $record->hubnetUser;
    }
}

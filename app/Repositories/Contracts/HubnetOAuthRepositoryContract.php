<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\HubnetAccessToken;
use App\Models\HubnetAuthorizationCode;
use App\Models\HubnetUser;

/**
 * Artefak OAuth yang berumur pendek: authorization code dan access token.
 *
 * Keduanya disatukan di sini karena selalu dipakai berpasangan dalam satu alur
 * (code diterbitkan → ditukar jadi token → token dibaca sekali) dan berbagi sifat
 * yang sama: disimpan sebagai hash, berumur pendek, dibersihkan berkala.
 */
interface HubnetOAuthRepositoryContract
{
    public function issueCode(HubnetUser $user, string $codeHash, string $clientId, string $redirectUri, int $ttlSeconds): HubnetAuthorizationCode;

    /**
     * Ambil code yang masih boleh ditukar, DENGAN penguncian baris — code sekali
     * pakai, pembacaan tanpa kunci membuat dua penukaran bersamaan sama-sama lolos.
     */
    public function findRedeemableCodeForUpdate(string $codeHash): ?HubnetAuthorizationCode;

    public function markCodeUsed(HubnetAuthorizationCode $code): void;

    public function issueToken(HubnetUser $user, string $tokenHash, int $ttlSeconds): HubnetAccessToken;

    public function findValidToken(string $tokenHash): ?HubnetAccessToken;

    /**
     * Buang code & token yang sudah lama mati. Tabel yang tumbuh selamanya adalah
     * utang yang menagih sendiri.
     */
    public function pruneExpired(): void;
}

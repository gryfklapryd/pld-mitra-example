<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Hasil penukaran authorization code menjadi access token.
 *
 * Membawa nilai token pada keberhasilan, atau kode galat OAuth2 pada kegagalan
 * (`invalid_grant` untuk code yang salah/kedaluwarsa/terpakai/tak cocok,
 * `invalid_client` untuk klien yang tak dikenal). Kode galat sengaja kasar —
 * membedakan "code kedaluwarsa" dari "code sudah dipakai" hanya menolong penebak.
 */
final readonly class TokenExchangeResult
{
    private function __construct(
        public bool $ok,
        public ?string $accessToken,
        public int $expiresIn,
        public ?string $error,
    ) {}

    public static function success(string $accessToken, int $expiresIn): self
    {
        return new self(true, $accessToken, $expiresIn, null);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, 0, $error);
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Menyusun klien OAuth dari config/hubnet.php dan menyelesaikan `client_id`.
 *
 * Tiruan ini melayani SATU klien (PLD), jadi registry-nya sesederhana mungkin —
 * tetapi tetap dilewatkan lewat satu titik supaya "klien yang dikenal" tidak
 * terpencar sebagai perbandingan string di beberapa controller.
 */
final class OAuthClientRegistry
{
    public function resolve(string $clientId): ?OAuthClient
    {
        $expectedId = (string) config('hubnet.client_id');
        $secret = (string) config('hubnet.client_secret');

        // Konfigurasi kosong TIDAK BOLEH berarti "klien mana pun sah". Sama seperti
        // VerifyPldApiKey: kegagalan konfigurasi harus menutup pintu, bukan membukanya.
        if ($expectedId === '' || $secret === '' || $clientId === '') {
            return null;
        }

        if (! hash_equals($expectedId, $clientId)) {
            return null;
        }

        /** @var array<int, string> $redirectUris */
        $redirectUris = (array) config('hubnet.redirect_uris', []);

        return new OAuthClient($expectedId, $secret, $redirectUris);
    }
}

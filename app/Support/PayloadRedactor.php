<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Menyamarkan nilai sensitif sebelum payload ditulis ke `integration_logs`.
 *
 * Ini bukan kehati-hatian berlebihan. `API User Validation URL` menerima
 * **password PLD milik member** dalam bentuk mentah — itu memang bentuk
 * kontraknya. Menyimpannya ke tabel log berarti membuat basis data aplikasi ini
 * menjadi tempat penyimpanan password bersih milik pengguna sistem LAIN, yang
 * bocornya jauh lebih mahal daripada bocornya data aplikasi ini sendiri.
 *
 * Kunci disamarkan berdasarkan NAMA, tanpa memandang letaknya, karena bentuk
 * payload bisa berubah tetapi nama field jarang berubah.
 */
final class PayloadRedactor
{
    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'secret',
        'api_key',
        'apikey',
        'service_key',
        'servicekey',
        'token',
        'auth_token',
        'authtoken',
        'authorization',
    ];

    private const MASK = '********';

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    public static function redact(array $payload): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            if (is_string($key) && self::isSensitive($key)) {
                $result[$key] = self::MASK;

                continue;
            }

            $result[$key] = is_array($value) ? self::redact($value) : $value;
        }

        return $result;
    }

    private static function isSensitive(string $key): bool
    {
        $normalized = mb_strtolower(str_replace(['-', '_', ' '], '', $key));

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($normalized === str_replace('_', '', $sensitive)) {
                return true;
            }
        }

        return false;
    }
}

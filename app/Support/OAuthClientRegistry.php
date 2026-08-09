<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\HubnetOAuthClient;
use App\Repositories\Contracts\HubnetClientRepositoryContract;

/**
 * Menyelesaikan `client_id` menjadi OAuthClient yang bisa diverifikasi.
 *
 * Dua sumber, dengan urutan sengaja:
 *
 *   1. BASIS DATA (dikelola panel operator) — sumber utama.
 *   2. config/hubnet.php (dari env) — CADANGAN, supaya klien yang sudah live
 *      lewat env tidak putus saat fitur DB ini dinyalakan sebelum di-seed.
 *
 * Konfigurasi/DB kosong = tolak (null), bukan "izinkan semua" — sama seperti
 * VerifyPldApiKey: kegagalan konfigurasi harus menutup pintu.
 */
final class OAuthClientRegistry
{
    public function __construct(
        private readonly HubnetClientRepositoryContract $clients,
    ) {}

    public function resolve(string $clientId): ?OAuthClient
    {
        if ($clientId === '') {
            return null;
        }

        $model = $this->clients->findActiveByClientId($clientId);

        if ($model instanceof HubnetOAuthClient) {
            return new OAuthClient($model->client_id, $model->client_secret, $model->redirect_uris);
        }

        return $this->resolveFromConfig($clientId);
    }

    private function resolveFromConfig(string $clientId): ?OAuthClient
    {
        $expectedId = (string) config('hubnet.client_id');
        $secret = (string) config('hubnet.client_secret');

        if ($expectedId === '' || $secret === '' || ! hash_equals($expectedId, $clientId)) {
            return null;
        }

        /** @var array<int, string> $redirectUris */
        $redirectUris = (array) config('hubnet.redirect_uris', []);

        return new OAuthClient($expectedId, $secret, $redirectUris);
    }
}

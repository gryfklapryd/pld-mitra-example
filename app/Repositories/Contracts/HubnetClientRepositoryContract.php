<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\HubnetOAuthClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface HubnetClientRepositoryContract
{
    /**
     * Klien AKTIF berdasarkan client_id — dipakai alur SSO. Klien nonaktif
     * dianggap tidak ada, supaya bisa dimatikan tanpa dihapus.
     */
    public function findActiveByClientId(string $clientId): ?HubnetOAuthClient;

    /**
     * @return LengthAwarePaginator<int, HubnetOAuthClient>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HubnetOAuthClient;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(HubnetOAuthClient $client, array $data): HubnetOAuthClient;

    public function delete(HubnetOAuthClient $client): void;

    /**
     * Sisipkan/segarkan klien default (dari config) tanpa menggandakan.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsertByClientId(string $clientId, array $attributes): HubnetOAuthClient;
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\HubnetOAuthClient;
use App\Repositories\Contracts\HubnetClientRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HubnetClientRepository implements HubnetClientRepositoryContract
{
    public function findActiveByClientId(string $clientId): ?HubnetOAuthClient
    {
        if ($clientId === '') {
            return null;
        }

        return HubnetOAuthClient::query()
            ->active()
            ->where('client_id', $clientId)
            ->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return HubnetOAuthClient::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): HubnetOAuthClient
    {
        return HubnetOAuthClient::query()->create($data);
    }

    public function update(HubnetOAuthClient $client, array $data): HubnetOAuthClient
    {
        $client->update($data);

        return $client;
    }

    public function delete(HubnetOAuthClient $client): void
    {
        $client->delete();
    }

    public function upsertByClientId(string $clientId, array $attributes): HubnetOAuthClient
    {
        return HubnetOAuthClient::query()->updateOrCreate(
            ['client_id' => $clientId],
            $attributes,
        );
    }
}

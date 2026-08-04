<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\IntegrationLog;
use App\Repositories\Contracts\IntegrationLogRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class IntegrationLogRepository implements IntegrationLogRepositoryContract
{
    public function record(array $attributes): IntegrationLog
    {
        return IntegrationLog::query()->create($attributes);
    }

    public function paginate(?string $direction = null, int $perPage = 25): LengthAwarePaginator
    {
        return IntegrationLog::query()
            ->when(
                $direction !== null,
                static fn ($query) => $query->where('direction', $direction),
            )
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function lastInboundAt(string $endpoint): ?string
    {
        $log = IntegrationLog::query()
            ->inbound()
            ->where('endpoint', $endpoint)
            ->orderByDesc('id')
            ->first();

        return $log?->created_at?->toDateTimeString();
    }
}

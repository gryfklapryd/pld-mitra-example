<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\IntegrationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IntegrationLogRepositoryContract
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function record(array $attributes): IntegrationLog;

    /**
     * @return LengthAwarePaginator<int, IntegrationLog>
     */
    public function paginate(?string $direction = null, int $perPage = 25): LengthAwarePaginator;

    public function lastInboundAt(string $endpoint): ?string;
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Application;
use App\Repositories\Contracts\ApplicationRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ApplicationRepository implements ApplicationRepositoryContract
{
    public function forMembersWithRelations(array $memberIds): Collection
    {
        if ($memberIds === []) {
            return collect();
        }

        return Application::query()
            ->with(['member', 'stages', 'documents', 'attributes'])
            ->whereIn('member_id', $memberIds)
            ->orderByDesc('status_changed_at')
            ->get();
    }

    public function findByExternalRef(string $externalRef): ?Application
    {
        return Application::query()
            ->with(['member', 'stages', 'documents', 'attributes'])
            ->where('external_ref', $externalRef)
            ->first();
    }

    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Application::query()
            ->with('member')
            ->orderByDesc('status_changed_at')
            ->paginate($perPage);
    }

    public function countForYear(int $year): int
    {
        return Application::query()
            ->whereYear('submitted_at', $year)
            ->count();
    }
}

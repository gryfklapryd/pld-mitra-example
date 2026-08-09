<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetUserRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HubnetUserRepository implements HubnetUserRepositoryContract
{
    public function findByCredential(HubnetUserType $type, string $username): ?HubnetUser
    {
        return HubnetUser::query()
            ->credential($type, $username)
            ->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return HubnetUser::query()
            ->orderBy('type')
            ->orderBy('username')
            ->paginate($perPage);
    }

    public function create(array $data): HubnetUser
    {
        return HubnetUser::query()->create($data);
    }

    public function update(HubnetUser $user, array $data): HubnetUser
    {
        $user->update($data);

        return $user;
    }

    public function delete(HubnetUser $user): void
    {
        $user->delete();
    }
}

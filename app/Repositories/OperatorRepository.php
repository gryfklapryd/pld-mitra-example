<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\OperatorRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OperatorRepository implements OperatorRepositoryContract
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return User::query()->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(User $operator, array $data): User
    {
        $operator->update($data);

        return $operator;
    }

    public function delete(User $operator): void
    {
        $operator->delete();
    }
}

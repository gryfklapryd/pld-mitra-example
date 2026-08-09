<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Operator internal = model User (guard `web`). Diberi repository sendiri agar
 * query-nya tidak bocor ke controller, konsisten dengan lapisan lain.
 */
interface OperatorRepositoryContract
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $operator, array $data): User;

    public function delete(User $operator): void;
}

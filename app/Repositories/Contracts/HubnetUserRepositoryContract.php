<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface HubnetUserRepositoryContract
{
    /**
     * Cari identitas berdasarkan (tipe, username) — tanpa memeriksa kata sandi.
     * Verifikasi kata sandi dilakukan pemanggil (Action), bukan repository.
     */
    public function findByCredential(HubnetUserType $type, string $username): ?HubnetUser;

    /**
     * @return LengthAwarePaginator<int, HubnetUser>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HubnetUser;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(HubnetUser $user, array $data): HubnetUser;

    public function delete(HubnetUser $user): void;
}

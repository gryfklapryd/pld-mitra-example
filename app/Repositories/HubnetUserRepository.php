<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetUserRepositoryContract;

final class HubnetUserRepository implements HubnetUserRepositoryContract
{
    public function findByCredential(HubnetUserType $type, string $username): ?HubnetUser
    {
        return HubnetUser::query()
            ->credential($type, $username)
            ->first();
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;

interface HubnetUserRepositoryContract
{
    /**
     * Cari identitas berdasarkan (tipe, username) — tanpa memeriksa kata sandi.
     * Verifikasi kata sandi dilakukan pemanggil (Action), bukan repository.
     */
    public function findByCredential(HubnetUserType $type, string $username): ?HubnetUser;
}

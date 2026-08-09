<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetUserRepositoryContract;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Memverifikasi kredensial yang diketik di halaman login Hubnet TIRUAN.
 *
 * DUMMY sepenuhnya: kata sandi yang dicocokkan adalah kata sandi seed, bukan
 * kredensial Kemenhub. Penting: penanda nonaktif (B5) TIDAK diperiksa di sini —
 * biar pld-user yang menolaknya, persis seperti Hubnet asli yang tetap
 * menerbitkan sesi lalu memaparkan statusnya di userinfo. Dengan begitu jalur
 * penolakan B5 di pld-user benar-benar teruji, bukan dilangkahi di hulu.
 */
final readonly class AuthenticateHubnetUserAction
{
    public function __construct(
        private HubnetUserRepositoryContract $users,
        private Hasher $hasher,
    ) {}

    public function __invoke(HubnetUserType $type, string $username, string $password): ?HubnetUser
    {
        $user = $this->users->findByCredential($type, $username);

        if (! $user instanceof HubnetUser) {
            // Cegah pembeda waktu "user ada / tidak" dengan tetap menghitung satu
            // hash meski tak ada barisnya. Murah, dan menutup satu kebocoran halus.
            $this->hasher->check($password, '$2y$12$'.str_repeat('.', 53));

            return null;
        }

        if (! $this->hasher->check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}

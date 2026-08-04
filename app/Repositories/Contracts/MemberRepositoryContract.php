<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Member;
use Illuminate\Support\Collection;

interface MemberRepositoryContract
{
    /**
     * Cari member berdasarkan `user_login`, tanpa memedulikan besar-kecil huruf.
     */
    public function findByUserLogin(string $userLogin): ?Member;

    /**
     * Ambil member aktif untuk sekumpulan `user_login` sekaligus.
     *
     * Bentuk bulk, bukan perulangan findByUserLogin: satu sinkronisasi membawa
     * sampai 200 login, dan versi per-login berarti 200 query untuk pekerjaan
     * yang selesai dalam satu.
     *
     * @param  array<int, string>  $userLogins
     * @return Collection<int, Member>
     */
    public function activeByUserLogins(array $userLogins): Collection;

    /**
     * @return Collection<int, Member>
     */
    public function all(): Collection;
}

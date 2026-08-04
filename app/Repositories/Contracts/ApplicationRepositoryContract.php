<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ApplicationRepositoryContract
{
    /**
     * Seluruh proses milik sekumpulan member, siap diserialkan ke kontrak.
     *
     * Relasi timeline/documents/attributes DIMUAT DI SINI (eager load). Kalau
     * dibiarkan lazy, satu sinkronisasi 200 member × 4 relasi menjadi ribuan query
     * dalam satu permintaan yang batas waktunya 30 detik — dan gejalanya di sisi
     * PLD hanyalah timeout tanpa sebab yang terbaca.
     *
     * @param  array<int, int>  $memberIds
     * @return Collection<int, Application>
     */
    public function forMembersWithRelations(array $memberIds): Collection;

    public function findByExternalRef(string $externalRef): ?Application;

    /**
     * @return LengthAwarePaginator<int, Application>
     */
    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator;

    /**
     * Nomor urut berikutnya untuk pembuatan `external_ref`.
     */
    public function countForYear(int $year): int;
}

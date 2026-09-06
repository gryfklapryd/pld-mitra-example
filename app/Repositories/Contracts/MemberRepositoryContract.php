<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MemberRepositoryContract
{
    /**
     * @return LengthAwarePaginator<int, Member>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Member;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Member $member, array $data): Member;

    public function delete(Member $member): void;

    /**
     * Cari member berdasarkan `user_login`, tanpa memedulikan besar-kecil huruf.
     */
    public function findByUserLogin(string $userLogin): ?Member;

    /**
     * Cari member lewat identitas PLD-nya.
     *
     * Dipakai provisioning SEBELUM mencocokkan email/NIP (kontrak §10):
     * `pld_user_id` tak pernah berubah walau member mengganti email di PLD,
     * sementara pencocokan lewat email akan menganggapnya orang baru lalu
     * membuatkan akun kedua untuk orang yang sama.
     */
    public function findByPldUserId(string $pldUserId): ?Member;

    /**
     * Cari member lewat email atau NIP — jalur cadangan provisioning untuk akun
     * yang sudah ada di sini jauh sebelum PLD mengenalnya.
     *
     * NIP diperiksa terhadap `user_login` karena di aplikasi ini pegawai memang
     * memakai NIP sebagai identitas masuknya.
     */
    public function findByIdentity(string $email, ?string $nip): ?Member;

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

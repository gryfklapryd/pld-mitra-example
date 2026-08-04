<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Member;
use App\Repositories\Contracts\MemberRepositoryContract;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

final class MemberRepository implements MemberRepositoryContract
{
    public function findByUserLogin(string $userLogin): ?Member
    {
        return Member::query()->byUserLogin($userLogin)->first();
    }

    public function activeByUserLogins(array $userLogins): Collection
    {
        $normalized = array_values(array_filter(array_map(
            static fn (string $login): string => mb_strtolower(trim($login)),
            $userLogins,
        )));

        if ($normalized === []) {
            return collect();
        }

        // LOWER() eksplisit, bukan menyandarkan diri pada collation kolom: kontrak
        // menyebut pencocokan case-insensitive sebagai perilaku yang dijanjikan, dan
        // aplikasi rujukan tidak boleh diam-diam bergantung pada setelan MySQL yang
        // tim lain mungkin tidak punya.
        return Member::query()
            ->active()
            ->whereIn(new Expression('LOWER(user_login)'), $normalized)
            ->get();
    }

    public function all(): Collection
    {
        return Member::query()->orderBy('name')->get();
    }
}

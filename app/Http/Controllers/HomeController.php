<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Contracts\ApplicationRepositoryContract;
use Illuminate\View\View;

/**
 * Beranda member — apa yang dilihat member setelah mendarat lewat SSO.
 *
 * Ada supaya alur SSO bisa dibuktikan ujung ke ujung: kalau token benar ditukar,
 * di sini terlihat namanya dan daftar prosesnya sendiri.
 */
final class HomeController extends Controller
{
    public function __construct(
        private readonly ApplicationRepositoryContract $applications,
    ) {}

    public function __invoke(): View
    {
        $member = auth()->guard('member')->user();

        return view('home', [
            'member' => $member,
            'applications' => $member === null
                ? collect()
                : $this->applications->forMembersWithRelations([$member->id]),
        ]);
    }
}

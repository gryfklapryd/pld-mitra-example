<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Member;
use App\Repositories\Contracts\MemberRepositoryContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Panel operator: kelola member layanan.
 *
 * Menggantikan perintah `pel:member`. `user_login` yang dibuat di sini adalah
 * nilai yang HARUS diketik member saat menautkan akun di portal PLD, dan yang
 * dikirim PLD pada `userLogins[]` saat menyinkronkan tracking.
 */
final class MemberController extends Controller
{
    public function __construct(
        private readonly MemberRepositoryContract $members,
    ) {}

    public function index(): View
    {
        return view('admin.members.index', [
            'members' => $this->members->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.members.create');
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = $this->members->create($request->payload());

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Member {$member->user_login} dibuat.");
    }

    public function edit(Member $member): View
    {
        return view('admin.members.edit', ['member' => $member]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $this->members->update($member, $request->payload());

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Member {$member->user_login} diperbarui.");
    }

    public function destroy(Member $member): RedirectResponse
    {
        $login = $member->user_login;
        $this->members->delete($member);

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Member {$login} dihapus beserta seluruh permohonannya.");
    }
}

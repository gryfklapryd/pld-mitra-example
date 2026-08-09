<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OperatorRequest;
use App\Models\User;
use App\Repositories\Contracts\OperatorRepositoryContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Panel operator: kelola operator internal (akun panel /admin).
 *
 * Menggantikan perintah `pel:operator`. Menghapus akun SENDIRI ditolak — dan itu
 * cukup menjamin panel tak pernah kehilangan seluruh pintu masuknya: seorang
 * operator hanya bisa menghapus operator LAIN, jadi yang tersisa minimal satu
 * (dirinya). Tak perlu penjaga "operator terakhir" terpisah.
 */
final class OperatorController extends Controller
{
    public function __construct(
        private readonly OperatorRepositoryContract $operators,
    ) {}

    public function index(): View
    {
        return view('admin.operators.index', [
            'operators' => $this->operators->paginate(),
            'currentId' => auth()->id(),
        ]);
    }

    public function create(): View
    {
        return view('admin.operators.create');
    }

    public function store(OperatorRequest $request): RedirectResponse
    {
        $operator = $this->operators->create($request->payload());

        return redirect()
            ->route('admin.operators.index')
            ->with('status', "Operator {$operator->email} dibuat.");
    }

    public function edit(User $operator): View
    {
        return view('admin.operators.edit', ['operator' => $operator]);
    }

    public function update(OperatorRequest $request, User $operator): RedirectResponse
    {
        $this->operators->update($operator, $request->payload());

        return redirect()
            ->route('admin.operators.index')
            ->with('status', "Operator {$operator->email} diperbarui.");
    }

    public function destroy(User $operator): RedirectResponse
    {
        if ($operator->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun operator yang sedang Anda gunakan.');
        }

        $email = $operator->email;
        $this->operators->delete($operator);

        return redirect()
            ->route('admin.operators.index')
            ->with('status', "Operator {$email} dihapus.");
    }
}

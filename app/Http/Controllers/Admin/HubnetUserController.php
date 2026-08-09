<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\HubnetUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HubnetUserRequest;
use App\Models\HubnetUser;
use App\Repositories\Contracts\HubnetUserRepositoryContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Panel operator: kelola identitas Hubnet TIRUAN (data DUMMY).
 *
 * Menggantikan ketergantungan pada seeder — akun uji bisa dibuat/diubah/dihapus
 * langsung dari layar, termasuk menandai nonaktif (untuk menguji B5) atau memberi
 * NIK/unit tertentu (untuk menguji jalur provisioning di pld-user).
 */
final class HubnetUserController extends Controller
{
    public function __construct(
        private readonly HubnetUserRepositoryContract $users,
    ) {}

    public function index(): View
    {
        return view('admin.hubnet-users.index', [
            'users' => $this->users->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.hubnet-users.create', [
            'types' => HubnetUserType::cases(),
        ]);
    }

    public function store(HubnetUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->payload());

        return redirect()
            ->route('admin.hubnet-users.index')
            ->with('status', "Identitas Hubnet {$user->username} dibuat.");
    }

    public function edit(HubnetUser $hubnet_user): View
    {
        return view('admin.hubnet-users.edit', [
            'user' => $hubnet_user,
            'types' => HubnetUserType::cases(),
        ]);
    }

    public function update(HubnetUserRequest $request, HubnetUser $hubnet_user): RedirectResponse
    {
        $this->users->update($hubnet_user, $request->payload());

        return redirect()
            ->route('admin.hubnet-users.index')
            ->with('status', "Identitas Hubnet {$hubnet_user->username} diperbarui.");
    }

    public function destroy(HubnetUser $hubnet_user): RedirectResponse
    {
        $username = $hubnet_user->username;
        $this->users->delete($hubnet_user);

        return redirect()
            ->route('admin.hubnet-users.index')
            ->with('status', "Identitas Hubnet {$username} dihapus.");
    }
}

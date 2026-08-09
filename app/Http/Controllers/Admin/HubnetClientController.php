<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HubnetClientRequest;
use App\Models\HubnetOAuthClient;
use App\Repositories\Contracts\HubnetClientRepositoryContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Panel operator: kelola klien OAuth yang boleh memakai SSO Hubnet TIRUAN.
 *
 * `client_id` dan `client_secret` dibangkitkan server (bukan diketik). Keduanya
 * disalin ke HUBNET_CLIENT_ID/HUBNET_CLIENT_SECRET milik aplikasi klien
 * (mis. pld-user), dan `redirect_uris` harus memuat {BO_DOMAIN}/hubnet/sso-nya.
 */
final class HubnetClientController extends Controller
{
    public function __construct(
        private readonly HubnetClientRepositoryContract $clients,
    ) {}

    public function index(): View
    {
        return view('admin.hubnet-clients.index', [
            'clients' => $this->clients->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.hubnet-clients.create');
    }

    public function store(HubnetClientRequest $request): RedirectResponse
    {
        $client = $this->clients->create([
            ...$request->payload(),
            'client_id' => (string) Str::uuid(),
            'client_secret' => $this->newSecret(),
        ]);

        // Ditampilkan menonjol sekali di layar berikutnya supaya bisa langsung
        // disalin ke env klien. Karena secret disimpan apa adanya (IdP uji), ia
        // tetap terlihat di halaman ubah — panel ini hanya kenyamanan.
        return redirect()
            ->route('admin.hubnet-clients.index')
            ->with('status', "Klien {$client->name} dibuat.")
            ->with('new_client', ['client_id' => $client->client_id, 'client_secret' => $client->client_secret]);
    }

    public function edit(HubnetOAuthClient $hubnet_client): View
    {
        return view('admin.hubnet-clients.edit', [
            'client' => $hubnet_client,
        ]);
    }

    public function update(HubnetClientRequest $request, HubnetOAuthClient $hubnet_client): RedirectResponse
    {
        $this->clients->update($hubnet_client, $request->payload());

        return redirect()
            ->route('admin.hubnet-clients.index')
            ->with('status', "Klien {$hubnet_client->name} diperbarui.");
    }

    public function regenerateSecret(HubnetOAuthClient $hubnet_client): RedirectResponse
    {
        $this->clients->update($hubnet_client, ['client_secret' => $this->newSecret()]);

        return redirect()
            ->route('admin.hubnet-clients.index')
            ->with('status', "Secret klien {$hubnet_client->name} dibangkitkan ulang — perbarui env klien.")
            ->with('new_client', ['client_id' => $hubnet_client->client_id, 'client_secret' => $hubnet_client->client_secret]);
    }

    public function destroy(HubnetOAuthClient $hubnet_client): RedirectResponse
    {
        $name = $hubnet_client->name;
        $this->clients->delete($hubnet_client);

        return redirect()
            ->route('admin.hubnet-clients.index')
            ->with('status', "Klien {$name} dihapus.");
    }

    private function newSecret(): string
    {
        return bin2hex(random_bytes(24));
    }
}

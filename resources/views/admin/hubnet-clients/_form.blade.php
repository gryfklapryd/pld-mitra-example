{{-- $client: HubnetOAuthClient|null · $action: url · $method: 'POST'|'PUT' --}}
@php($c = $client ?? null)
@php($oldUris = old('redirect_uris'))
@php($urisText = is_array($oldUris) ? implode("\n", $oldUris) : ($oldUris ?? ($c ? implode("\n", $c->redirect_uris) : '')))

<form method="POST" action="{{ $action }}"
      class="max-w-2xl space-y-5 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @if (($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama aplikasi</label>
        <input id="name" name="name" required maxlength="190" value="{{ old('name', $c?->name) }}"
               placeholder="mis. PLD (pld-dev)"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
    </div>

    <div>
        <label for="redirect_uris" class="mb-1 block text-sm font-medium text-gray-700">Redirect URI (satu per baris)</label>
        <textarea id="redirect_uris" name="redirect_uris" rows="3"
                  placeholder="https://pld-dev.ortala-djpu.my.id/hubnet/sso"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ $urisText }}</textarea>
        <p class="mt-1 text-xs text-gray-500">
            Harus memuat <span class="font-mono">{BO_DOMAIN}/hubnet/sso</span> milik aplikasi klien. Dicocokkan sama persis saat authorize & tukar token.
        </p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $c?->is_active ?? true)) class="rounded border-gray-300">
        Aktif <span class="text-xs text-gray-400">(nonaktif → klien ditolak tanpa perlu dihapus)</span>
    </label>

    @if ($c)
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-xs">
            <p class="mb-2 font-semibold text-gray-600">Kredensial (salin ke env aplikasi klien)</p>
            <p class="text-gray-500">HUBNET_CLIENT_ID</p>
            <p class="mb-2 break-all font-mono text-gray-800">{{ $c->client_id }}</p>
            <p class="text-gray-500">HUBNET_CLIENT_SECRET</p>
            <p class="break-all font-mono text-gray-800">{{ $c->client_secret }}</p>
        </div>
    @endif

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Simpan
        </button>
        <a href="{{ route('admin.hubnet-clients.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
    </div>
</form>

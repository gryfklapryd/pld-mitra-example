@extends('layouts.app')

@section('title', 'Klien SSO — PEL')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Klien SSO Hubnet TIRUAN</h1>
            <p class="mt-0.5 text-sm text-gray-500">Aplikasi yang boleh menukar code lewat IdP ini.</p>
        </div>
        <a href="{{ route('admin.hubnet-clients.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Klien baru
        </a>
    </div>

    @if (session('new_client'))
        @php($nc = session('new_client'))
        <div class="mb-5 rounded-xl border border-green-300 bg-green-50 p-4 text-sm">
            <p class="mb-2 font-semibold text-green-900">Kredensial klien — salin ke env aplikasi klien (mis. pld-user):</p>
            <div class="space-y-1 font-mono text-xs text-green-900">
                <p>HUBNET_CLIENT_ID=<span class="break-all">{{ $nc['client_id'] }}</span></p>
                <p>HUBNET_CLIENT_SECRET=<span class="break-all">{{ $nc['client_secret'] }}</span></p>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">client_id</th>
                    <th class="px-4 py-3">Redirect URI</th>
                    <th class="px-4 py-3">Aktif</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $client)
                    <tr>
                        <td class="px-4 py-3 text-gray-800">{{ $client->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $client->client_id }}</td>
                        <td class="px-4 py-3">
                            <ul class="space-y-0.5 font-mono text-xs text-gray-600">
                                @foreach ($client->redirect_uris as $uri)
                                    <li class="break-all">{{ $uri }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-4 py-3">
                            @if ($client->is_active)
                                <span class="rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-800">aktif</span>
                            @else
                                <span class="rounded bg-gray-200 px-1.5 py-0.5 text-xs text-gray-600">nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.hubnet-clients.edit', $client) }}" class="text-blue-600 hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('admin.hubnet-clients.destroy', $client) }}"
                                      onsubmit="return confirm('Hapus klien {{ $client->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Belum ada klien. Klien default dari env muncul setelah <span class="font-mono">php artisan hubnet:seed</span>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $clients->links() }}</div>
@endsection

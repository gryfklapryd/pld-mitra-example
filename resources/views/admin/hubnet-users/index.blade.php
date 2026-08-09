@extends('layouts.app')

@section('title', 'Identitas Hubnet — PEL')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Identitas Hubnet (dummy)</h1>
            <p class="mt-0.5 text-sm text-gray-500">Akun yang bisa dipakai login di halaman SSO Hubnet TIRUAN.</p>
        </div>
        <a href="{{ route('admin.hubnet-users.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Identitas baru
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Username</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">NIK</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-semibold uppercase text-gray-700">{{ $user->type->radioLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ $user->username }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $user->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $user->nik ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($user->is_deleted)
                                <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-800">terhapus</span>
                            @elseif ($user->status)
                                <span class="rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-800">aktif</span>
                            @else
                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-900">nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.hubnet-users.edit', $user) }}" class="text-blue-600 hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('admin.hubnet-users.destroy', $user) }}"
                                      onsubmit="return confirm('Hapus identitas {{ $user->username }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Belum ada identitas. Jalankan <span class="font-mono">php artisan hubnet:seed</span> atau buat baru.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

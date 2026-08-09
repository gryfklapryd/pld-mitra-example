@extends('layouts.app')

@section('title', 'Member — PEL')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Member layanan</h1>
            <p class="mt-0.5 text-sm text-gray-500">Pengguna aplikasi ini yang dikenal PLD lewat <span class="font-mono">user_login</span>.</p>
        </div>
        <a href="{{ route('admin.members.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Member baru
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">user_login</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ $member->user_login }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $member->email ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($member->is_active)
                                <span class="rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-800">aktif</span>
                            @else
                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-900">nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.members.edit', $member) }}" class="text-blue-600 hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('admin.members.destroy', $member) }}"
                                      onsubmit="return confirm('Hapus member {{ $member->user_login }} beserta SELURUH permohonannya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada member. Buat baru untuk mulai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $members->links() }}</div>
@endsection

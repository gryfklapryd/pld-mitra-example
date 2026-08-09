@extends('layouts.app')

@section('title', 'Operator — PEL')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Operator internal</h1>
            <p class="mt-0.5 text-sm text-gray-500">Akun pemegang akses panel <span class="font-mono">/admin</span>.</p>
        </div>
        <a href="{{ route('admin.operators.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Operator baru
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($operators as $operator)
                    <tr>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $operator->name }}
                            @if ($operator->id === $currentId)
                                <span class="ml-1 rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-blue-700">Anda</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $operator->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.operators.edit', $operator) }}" class="text-blue-600 hover:underline">Ubah</a>
                                @if ($operator->id === $currentId)
                                    <span class="text-gray-300" title="Tidak bisa menghapus akun sendiri">Hapus</span>
                                @else
                                    <form method="POST" action="{{ route('admin.operators.destroy', $operator) }}"
                                          onsubmit="return confirm('Hapus operator {{ $operator->email }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">Belum ada operator.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $operators->links() }}</div>
@endsection

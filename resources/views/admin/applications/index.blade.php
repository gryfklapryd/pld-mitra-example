@extends('layouts.app')

@section('title', 'Permohonan — PEL')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Permohonan</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Inilah yang dijawab <span class="font-mono text-xs">API Tracking URL</span> saat PLD menyinkronkan.
            </p>
        </div>

        <a href="{{ route('admin.applications.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Buat permohonan
        </a>
    </div>

    @if ($applications->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-sm text-gray-500">Belum ada permohonan.</p>
            <p class="mt-1 text-xs text-gray-400">
                Jalankan <span class="font-mono">php artisan db:seed</span> untuk data contoh,
                atau buat satu lewat tombol di atas.
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">externalRef</th>
                            <th class="px-4 py-3 font-semibold">Proses</th>
                            <th class="px-4 py-3 font-semibold">Member</th>
                            <th class="px-4 py-3 font-semibold">Kategori</th>
                            <th class="px-4 py-3 font-semibold">Tahap</th>
                            <th class="px-4 py-3 font-semibold">Status berubah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($applications as $application)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.applications.show', $application) }}"
                                       class="font-mono text-xs font-medium text-blue-700 hover:underline">
                                        {{ $application->external_ref }}
                                    </a>
                                </td>
                                <td class="max-w-xs px-4 py-3">
                                    <p class="truncate text-gray-800">{{ $application->label }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $application->status_label }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-gray-700">{{ $application->member->name }}</p>
                                    <p class="font-mono text-xs text-gray-400">{{ $application->member->user_login }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $application->category->badgeClass() }}">
                                        {{ $application->category->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ $application->current_stage }} / {{ $application->total_stages }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                    {{ $application->status_changed_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $applications->links() }}
        </div>
    @endif
@endsection

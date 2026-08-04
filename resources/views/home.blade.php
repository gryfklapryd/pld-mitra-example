@extends('layouts.app')

@section('title', 'Beranda — PEL')

@section('content')
    @if ($member)
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-5">
            <p class="text-sm font-semibold text-green-900">Masuk lewat SSO PLD</p>
            <p class="mt-0.5 text-sm text-green-800">
                {{ $member->name }}
                (<span class="font-mono text-xs">{{ $member->user_login }}</span>)
            </p>
        </div>

        <h1 class="mb-3 text-lg font-bold text-gray-800">Permohonan Anda</h1>

        @forelse ($applications as $application)
            <a href="{{ route('permohonan.show', $application->external_ref) }}"
               class="mb-3 block rounded-xl border border-gray-200 bg-white p-5 hover:border-blue-300">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-800">{{ $application->label }}</p>
                        <p class="mt-0.5 font-mono text-xs text-gray-400">{{ $application->external_ref }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $application->category->badgeClass() }}">
                        {{ $application->category->label() }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-600">{{ $application->status_label }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    Tahap {{ $application->current_stage }} dari {{ $application->total_stages }}
                </p>
            </a>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                Belum ada permohonan atas nama Anda.
            </p>
        @endforelse
    @else
        <div class="rounded-xl border border-gray-200 bg-white p-8">
            <h1 class="text-lg font-bold text-gray-800">PEL — Perizinan Elektronik</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                Aplikasi contoh yang bertindak sebagai <strong>mitra PLD</strong>. Ia mengimplementasikan
                ketiga endpoint kontrak integrasi dan bisa dipakai untuk menguji tracking &amp; notifikasi
                ujung ke ujung tanpa menunggu proses perizinan sungguhan.
            </p>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/auth</p>
                    <p class="mt-1 text-xs text-gray-500">API Auth URL — tukar user_login jadi token SSO.</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/user/validation</p>
                    <p class="mt-1 text-xs text-gray-500">API User Validation URL — cocokkan kredensial saat menautkan akun.</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/tracking</p>
                    <p class="mt-1 text-xs text-gray-500">API Tracking URL — laporkan proses member (Jalur A).</p>
                </div>
            </div>

            <p class="mt-5 text-xs leading-relaxed text-gray-500">
                Anda belum masuk. Member mendarat di sini lewat
                <span class="font-mono">{{ route('sso.landing') }}?pld_auth=&lt;token&gt;</span>
                setelah menekan layanan ini di portal PLD — atau bisa
                <a href="{{ route('masuk') }}" class="text-blue-700 hover:underline">masuk langsung</a>
                dengan kredensial PEL, yaitu pasangan yang sama yang diverifikasi
                <span class="font-mono">API User Validation URL</span>.
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('masuk') }}"
                   class="inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Masuk sebagai member
                </a>
                <a href="{{ route('operator.masuk') }}"
                   class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Panel operator
                </a>
            </div>
        </div>
    @endif
@endsection

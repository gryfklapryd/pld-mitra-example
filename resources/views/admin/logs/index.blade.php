@extends('layouts.app')

@section('title', 'Log Integrasi — PEL')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-800">Log Integrasi</h1>
        <p class="mt-0.5 text-sm text-gray-500">
            Jejak dua arah. Password dan kunci sudah disamarkan sebelum disimpan.
        </p>
    </div>

    <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-700">
            Sinkronisasi tracking terakhir dari PLD:
            @if ($lastTrackingAt)
                <span class="font-medium text-green-700">{{ $lastTrackingAt }}</span>
            @else
                <span class="font-medium text-amber-700">belum pernah</span>
                <span class="text-gray-500">
                    — pastikan <span class="font-mono text-xs">API Tracking URL</span> sudah diisi di form Aplikasi PLD
                    dan <span class="font-mono text-xs">PLD_API_KEY</span> di .env cocok.
                </span>
            @endif
        </p>
    </div>

    <div class="mb-4 flex gap-1 text-sm">
        <a href="{{ route('admin.logs.index') }}"
           class="rounded-md px-3 py-1.5 {{ $direction === null ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
            Semua
        </a>
        <a href="{{ route('admin.logs.index', ['direction' => 'INBOUND']) }}"
           class="rounded-md px-3 py-1.5 {{ $direction === 'INBOUND' ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
            Masuk (PLD &rarr; kita)
        </a>
        <a href="{{ route('admin.logs.index', ['direction' => 'OUTBOUND']) }}"
           class="rounded-md px-3 py-1.5 {{ $direction === 'OUTBOUND' ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
            Keluar (kita &rarr; PLD)
        </a>
    </div>

    @if ($logs->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-sm text-gray-500">Belum ada panggilan tercatat.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($logs as $log)
                <details class="group overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <summary class="flex cursor-pointer flex-wrap items-center gap-3 px-4 py-3 hover:bg-gray-50">
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $log->direction === 'INBOUND' ? 'bg-indigo-100 text-indigo-800' : 'bg-purple-100 text-purple-800' }}">
                            {{ $log->direction === 'INBOUND' ? 'MASUK' : 'KELUAR' }}
                        </span>

                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-gray-800">{{ $log->endpoint }}</span>

                        <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $log->isSuccessful() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $log->http_status ?? 'gagal' }}
                        </span>

                        @if ($log->duration_ms !== null)
                            <span class="text-xs text-gray-400">{{ $log->duration_ms }} ms</span>
                        @endif

                        <span class="text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                    </summary>

                    <div class="grid gap-4 border-t border-gray-100 bg-gray-50/60 p-4 lg:grid-cols-2">
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Permintaan</p>
                            <pre class="max-h-72 overflow-auto rounded-lg bg-gray-900 p-3 text-[11px] leading-relaxed text-gray-100"><code>{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Jawaban</p>
                            <pre class="max-h-72 overflow-auto rounded-lg bg-gray-900 p-3 text-[11px] leading-relaxed text-gray-100"><code>{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>

                        @if ($log->error_message)
                            <div class="lg:col-span-2">
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-red-600">Galat</p>
                                <p class="rounded-lg bg-red-50 p-3 text-xs leading-relaxed text-red-900">{{ $log->error_message }}</p>
                            </div>
                        @endif

                        @if ($log->remote_ip)
                            <p class="text-xs text-gray-400 lg:col-span-2">Dari IP {{ $log->remote_ip }}</p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
@endsection

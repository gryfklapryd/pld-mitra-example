@extends('layouts.app')

@section('title', $application->external_ref.' — PEL')

@section('content')
    {{-- Halaman tujuan `detailUrl`. Inilah yang dibuka member ketika ia menekan
         "Buka di layanan" dari panel tracking di portal PLD. --}}

    <div class="mx-auto max-w-3xl">
        @if ($viewedAsOperator ?? false)
            <div class="mb-4 rounded-lg border-l-4 border-indigo-400 bg-indigo-50 p-3 text-xs text-indigo-900">
                Anda membuka halaman ini sebagai <strong>operator</strong>, bukan sebagai pemilik permohonan.
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-gray-800">{{ $application->label }}</h1>
                    <p class="mt-0.5 font-mono text-xs text-gray-400">{{ $application->external_ref }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $application->category->badgeClass() }}">
                    {{ $application->category->label() }}
                </span>
            </div>

            <p class="mt-3 text-sm text-gray-700">{{ $application->status_label }}</p>

            <div class="mt-4">
                <div class="mb-1 flex justify-between text-xs text-gray-500">
                    <span>Tahap {{ $application->current_stage }} dari {{ $application->total_stages }}</span>
                    <span>Diperbarui {{ $application->status_changed_at->diffForHumans() }}</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-blue-500"
                         style="width: {{ min(100, (int) round($application->current_stage / max(1, $application->total_stages) * 100)) }}%"></div>
                </div>
            </div>

            @if ($application->category->requiresAction() && $application->action_instruction)
                <div class="mt-4 rounded-lg border-l-4 border-amber-400 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Perlu tindakan Anda</p>
                    <p class="mt-0.5 leading-relaxed">{{ $application->action_instruction }}</p>
                    @if ($application->action_due_at)
                        <p class="mt-1 text-xs">Batas waktu: {{ $application->action_due_at->translatedFormat('d F Y') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Perjalanan Proses</h2>

            <ol class="space-y-4">
                @foreach ($application->stages as $stage)
                    @php
                        $isDone = $stage->stage < $application->current_stage
                            || ($application->isTerminal() && $stage->stage <= $application->current_stage);
                        $isCurrent = ! $application->isTerminal() && $stage->stage === $application->current_stage;
                    @endphp
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            @if ($isDone)
                                <span class="h-3 w-3 rounded-full bg-green-500"></span>
                            @elseif ($isCurrent)
                                <span class="h-3 w-3 rounded-full bg-blue-500 ring-4 ring-blue-100"></span>
                            @else
                                <span class="h-3 w-3 rounded-full border-2 border-gray-300"></span>
                            @endif
                            @if (! $loop->last)
                                <span class="mt-1 w-px flex-1 bg-gray-200"></span>
                            @endif
                        </div>
                        <div class="pb-2 text-sm {{ ! $isDone && ! $isCurrent ? 'text-gray-400' : 'text-gray-800' }}">
                            <p class="{{ $isCurrent ? 'font-semibold' : 'font-medium' }}">
                                {{ $stage->stage }}. {{ $stage->name }}
                            </p>
                            @if ($stage->occurred_at && ($isDone || $isCurrent))
                                <p class="text-xs text-gray-500">{{ $stage->occurred_at->translatedFormat('d M Y H:i') }}</p>
                            @endif
                            @if ($stage->actor)
                                <p class="text-xs text-gray-500">{{ $stage->actor }}</p>
                            @endif
                            @if ($stage->note)
                                <p class="mt-1 text-xs leading-relaxed text-gray-600">{{ $stage->note }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        @if ($application->attributes->isNotEmpty())
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Keterangan</h2>
                <dl class="grid gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                    @foreach ($application->attributes as $attribute)
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">{{ $attribute->label }}</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $attribute->value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        @if ($application->contact_unit)
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-6 text-sm">
                <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Hubungi</h2>
                <p class="text-gray-800">{{ $application->contact_unit }}</p>
                @if ($application->contact_email)
                    <p class="text-gray-600">{{ $application->contact_email }}</p>
                @endif
                @if ($application->contact_phone)
                    <p class="text-gray-600">{{ $application->contact_phone }}</p>
                @endif
            </div>
        @endif
    </div>
@endsection

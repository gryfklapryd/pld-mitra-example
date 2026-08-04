@extends('layouts.app')

@section('title', $application->external_ref.' — PEL')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.applications.index') }}" class="text-xs text-gray-500 hover:underline">&larr; Semua permohonan</a>
        <h1 class="mt-1 text-xl font-bold text-gray-800">{{ $application->label }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">
            <span class="font-mono text-xs">{{ $application->external_ref }}</span>
            &middot; {{ $application->member->name }}
            (<span class="font-mono text-xs">{{ $application->member->user_login }}</span>)
        </p>
    </div>

    <div class="grid gap-5 lg:grid-cols-5">
        {{-- Kolom kiri: keadaan & tombol --}}
        <div class="space-y-5 lg:col-span-3">

            <section class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $application->category->badgeClass() }}">
                        {{ $application->category->label() }}
                    </span>
                    <span class="text-xs text-gray-500">
                        Tahap {{ $application->current_stage }} dari {{ $application->total_stages }}
                    </span>
                </div>

                <p class="text-sm text-gray-700">{{ $application->status_label }}</p>

                <ol class="mt-4 space-y-3">
                    @foreach ($preview['timeline'] as $entry)
                        <li class="flex gap-3">
                            <div class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center">
                                @if ($entry['status'] === 'DONE')
                                    <span class="h-3 w-3 rounded-full bg-green-500"></span>
                                @elseif ($entry['status'] === 'CURRENT')
                                    <span class="h-3 w-3 animate-pulse rounded-full bg-blue-500 ring-4 ring-blue-100"></span>
                                @else
                                    <span class="h-3 w-3 rounded-full border-2 border-gray-300"></span>
                                @endif
                            </div>
                            <div class="min-w-0 text-sm {{ $entry['status'] === 'PENDING' ? 'text-gray-400' : 'text-gray-800' }}">
                                <p class="{{ $entry['status'] === 'CURRENT' ? 'font-semibold' : 'font-medium' }}">
                                    {{ $entry['stage'] }}. {{ $entry['name'] }}
                                    <span class="ml-1 font-mono text-[10px] uppercase tracking-wide text-gray-400">{{ $entry['status'] }}</span>
                                </p>
                                @if ($entry['actor'])
                                    <p class="text-xs text-gray-500">{{ $entry['actor'] }}</p>
                                @endif
                                @if ($entry['note'])
                                    <p class="mt-0.5 text-xs leading-relaxed text-gray-600">{{ $entry['note'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Naikkan tahap --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="mb-1 font-semibold text-gray-800">Naikkan tahap</h2>
                <p class="mb-3 text-xs leading-relaxed text-gray-500">
                    Di sisi PLD ini menerbitkan notifikasi <strong>in-app saja</strong>, tanpa email —
                    lima tahap seharusnya menghasilkan satu-dua email, bukan lima.
                </p>

                @if ($application->isTerminal())
                    <p class="rounded-lg bg-gray-50 p-3 text-xs text-gray-500">
                        Proses sudah berakhir ({{ $application->category->label() }}) — tahapnya tidak bisa dinaikkan lagi.
                    </p>
                @elseif ($application->current_stage >= $application->total_stages)
                    <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                        Sudah di tahap terakhir. Selesaikan atau akhiri prosesnya lewat panel kategori.
                    </p>
                @else
                    <form method="POST" action="{{ route('admin.applications.advance', $application) }}" class="space-y-3">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input name="actor" maxlength="120" placeholder="Unit penangan (mis. Tim Teknis DKPPU)"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input name="note" maxlength="500" placeholder="Catatan tahap (dibaca member)"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <button type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Naikkan ke tahap {{ $application->current_stage + 1 }}
                        </button>
                    </form>
                @endif
            </section>

            {{-- Ubah kategori --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="mb-1 font-semibold text-gray-800">Ubah kategori</h2>
                <p class="mb-3 text-xs leading-relaxed text-gray-500">
                    Perpindahan ke <span class="font-mono">ACTION_REQUIRED</span>,
                    <span class="font-mono">COMPLETED</span>, dan kategori terminal negatif
                    menerbitkan <strong>in-app + EMAIL</strong> ke member.
                </p>

                <form method="POST" action="{{ route('admin.applications.category', $application) }}" class="space-y-3">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="category" class="mb-1 block text-xs font-medium text-gray-700">Kategori</label>
                            <select id="category" name="category" required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected(old('category', $application->category->value) === $category->value)>
                                        {{ $category->value }} — {{ $category->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="status_label" class="mb-1 block text-xs font-medium text-gray-700">
                                statusLabel <span class="font-normal text-gray-400">(maks 120)</span>
                            </label>
                            <input id="status_label" name="status_label" required maxlength="120"
                                   value="{{ old('status_label', $application->status_label) }}"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-3">
                        <p class="mb-2 text-xs font-semibold text-amber-900">
                            Hanya untuk ACTION_REQUIRED
                        </p>
                        <p class="mb-2 text-xs leading-relaxed text-amber-800">
                            Wajib diisi bila kategorinya ACTION_REQUIRED, dan wajib <strong>kosong</strong> untuk kategori lain.
                            Mengisinya pada kategori lain membuat seluruh item ditolak PLD.
                        </p>
                        <div class="space-y-2">
                            <input name="action_instruction" maxlength="500"
                                   value="{{ old('action_instruction', $application->action_instruction) }}"
                                   placeholder="Unggah ulang sertifikat kelaikan yang masih berlaku."
                                   class="w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input name="action_due_at" type="date"
                                       value="{{ old('action_due_at', $application->action_due_at?->format('Y-m-d')) }}"
                                       class="w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                                <input name="action_url" type="url" maxlength="500"
                                       value="{{ old('action_url', $application->action_url) }}"
                                       placeholder="https://…/perbaikan"
                                       class="w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="note" class="mb-1 block text-xs font-medium text-gray-700">
                            Catatan pada tahap berjalan
                        </label>
                        <input id="note" name="note" maxlength="500"
                               placeholder="Alasan penolakan/pencabutan — PLD memakainya sebagai isi email"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <button type="submit"
                            class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                        Terapkan
                    </button>
                </form>
            </section>
        </div>

        {{-- Kolom kanan: pratinjau payload --}}
        <div class="lg:col-span-2">
            <section class="sticky top-4 rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="font-semibold text-gray-800">Pratinjau payload</h2>
                <p class="mt-0.5 mb-3 text-xs leading-relaxed text-gray-500">
                    Persis satu entri <span class="font-mono">items[]</span> yang akan diterima PLD.
                    Periksa di sini sebelum menunggu siklus 30 menit.
                </p>

                <pre class="max-h-[32rem] overflow-auto rounded-lg bg-gray-900 p-4 text-[11px] leading-relaxed text-gray-100"><code>{{ json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>

                <dl class="mt-4 space-y-1.5 text-xs">
                    <div class="flex justify-between gap-3 border-b border-gray-100 pb-1">
                        <dt class="text-gray-500">Tahap CURRENT</dt>
                        <dd class="font-medium text-gray-800">
                            {{ collect($preview['timeline'])->where('status', 'CURRENT')->count() }}
                            <span class="font-normal text-gray-400">
                                (kontrak: {{ $application->isTerminal() ? '0 untuk item terminal' : 'tepat 1' }})
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-gray-100 pb-1">
                        <dt class="text-gray-500">actionRequired</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $preview['actionRequired'] === null ? 'null' : 'terisi' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">detailUrl</dt>
                        <dd class="max-w-[60%] truncate font-mono text-[10px] text-gray-600">{{ $preview['detailUrl'] }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
@endsection

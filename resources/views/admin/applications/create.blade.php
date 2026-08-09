@extends('layouts.app')

@section('title', 'Buat permohonan — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Buat permohonan</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">
        Seluruh tahap dibuat sekaligus di depan — termasuk yang belum tercapai.
        Kontrak menganjurkannya supaya member melihat apa yang menunggu, bukan hanya yang sudah lewat.
    </p>

    <form method="POST" action="{{ route('admin.applications.store') }}"
          class="max-w-2xl space-y-5 rounded-xl border border-gray-200 bg-white p-6">
        @csrf

        <div>
            <label for="member_id" class="mb-1 block text-sm font-medium text-gray-700">Member</label>
            <select id="member_id" name="member_id" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>
                        {{ $member->name }} ({{ $member->user_login }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">
                <span class="font-mono">user_login</span> inilah yang dicocokkan PLD — harus sama persis dengan yang diketik member saat menautkan akun.
            </p>
        </div>

        <div>
            <label for="label" class="mb-1 block text-sm font-medium text-gray-700">
                Nama proses <span class="font-normal text-gray-400">(maks 200 karakter)</span>
            </label>
            <input id="label" name="label" required maxlength="200" value="{{ old('label') }}"
                   placeholder="Perpanjangan Izin Usaha Angkutan Udara Niaga"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            <p class="mt-1 text-xs text-gray-500">Ditulis sebagaimana member mengenalnya, bukan kode internal.</p>
        </div>

        <div>
            <span class="mb-1 block text-sm font-medium text-gray-700">Tahap proses</span>

            @php
                $defaultStages = [
                    'Permohonan diterima',
                    'Verifikasi kelengkapan administrasi',
                    'Verifikasi teknis',
                    'Persetujuan Direktur',
                    'Penerbitan izin',
                ];
                $stageValues = array_values(array_filter(
                    array_map(fn ($v) => trim((string) $v), (array) old('stage_names', $defaultStages)),
                    fn ($v) => $v !== '',
                ));
                $stageValues = $stageValues ?: [''];
                $maxStages = (int) config('pld.limits.timeline_entries');
            @endphp

            <div id="stages" class="space-y-2" data-max="{{ $maxStages }}">
                @foreach ($stageValues as $value)
                    <div class="stage-row flex items-center gap-2">
                        <span class="stage-num w-6 shrink-0 text-right text-xs text-gray-400"></span>
                        <input name="stage_names[]" maxlength="120" value="{{ $value }}"
                               placeholder="Nama tahap"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <button type="button" class="stage-remove shrink-0 rounded-md px-3 py-2 text-sm text-gray-400 hover:bg-gray-100 hover:text-red-600" aria-label="Hapus tahap">&times;</button>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 flex items-center gap-3">
                <button type="button" id="stage-add"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    + Tambah tahap
                </button>
                <span id="stage-count" class="text-xs text-gray-400"></span>
            </div>

            <p class="mt-1 text-xs text-gray-500">
                Nama tahap dibaca member di portal PLD dan ikut ke isi notifikasi kenaikan tahap.
                Baris kosong diabaikan; maksimal <span class="font-mono">{{ $maxStages }}</span> tahap
                (batas kontrak §7, <span class="font-mono">pld.limits.timeline_entries</span>).
            </p>

            <script>
                (function () {
                    const list = document.getElementById('stages');
                    if (!list) return;
                    const addBtn = document.getElementById('stage-add');
                    const countEl = document.getElementById('stage-count');
                    const MAX = parseInt(list.dataset.max, 10) || 50;

                    function newRow() {
                        const row = document.createElement('div');
                        row.className = 'stage-row flex items-center gap-2';
                        row.innerHTML =
                            '<span class="stage-num w-6 shrink-0 text-right text-xs text-gray-400"></span>' +
                            '<input name="stage_names[]" maxlength="120" placeholder="Nama tahap" ' +
                            'class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">' +
                            '<button type="button" class="stage-remove shrink-0 rounded-md px-3 py-2 text-sm text-gray-400 hover:bg-gray-100 hover:text-red-600" aria-label="Hapus tahap">×</button>';
                        return row;
                    }

                    function refresh() {
                        const rows = list.querySelectorAll('.stage-row');
                        const only = rows.length <= 1;
                        rows.forEach(function (row, i) {
                            row.querySelector('.stage-num').textContent = (i + 1) + '.';
                            const rm = row.querySelector('.stage-remove');
                            rm.disabled = only;
                            rm.classList.toggle('opacity-30', only);
                            rm.classList.toggle('cursor-not-allowed', only);
                        });
                        const atMax = rows.length >= MAX;
                        addBtn.disabled = atMax;
                        addBtn.classList.toggle('opacity-40', atMax);
                        addBtn.classList.toggle('cursor-not-allowed', atMax);
                        countEl.textContent = rows.length + ' / ' + MAX + ' tahap'
                            + (atMax ? ' (maksimum)' : '');
                    }

                    addBtn.addEventListener('click', function () {
                        if (list.querySelectorAll('.stage-row').length >= MAX) return;
                        const row = newRow();
                        list.appendChild(row);
                        row.querySelector('input').focus();
                        refresh();
                    });

                    list.addEventListener('click', function (e) {
                        const btn = e.target.closest('.stage-remove');
                        if (!btn || btn.disabled) return;
                        if (list.querySelectorAll('.stage-row').length <= 1) return;
                        btn.closest('.stage-row').remove();
                        refresh();
                    });

                    refresh();
                })();
            </script>
        </div>

        <fieldset class="space-y-3 rounded-lg border border-gray-200 p-4">
            <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                Kontak (opsional)
            </legend>

            <div>
                <label for="contact_unit" class="mb-1 block text-sm text-gray-700">Unit / jabatan</label>
                <input id="contact_unit" name="contact_unit" maxlength="200" value="{{ old('contact_unit') }}"
                       placeholder="Direktorat Kelaikudaraan dan Pengoperasian Pesawat Udara"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-amber-700">
                    Unit atau jabatan — <strong>bukan nama perorangan</strong>. Isinya tampil di portal PLD dan ikut ke email member.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <input name="contact_email" type="email" maxlength="190" value="{{ old('contact_email') }}"
                       placeholder="unit@example.go.id"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <input name="contact_phone" maxlength="50" value="{{ old('contact_phone') }}"
                       placeholder="021-3506664"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </fieldset>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Simpan
            </button>
            <a href="{{ route('admin.applications.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
        </div>
    </form>
@endsection

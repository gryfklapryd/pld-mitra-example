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
            <div class="space-y-2">
                @for ($i = 0; $i < 6; $i++)
                    <input name="stage_names[]" maxlength="120"
                           value="{{ old('stage_names.'.$i, $i < 5 ? [
                               'Permohonan diterima',
                               'Verifikasi kelengkapan administrasi',
                               'Verifikasi teknis',
                               'Persetujuan Direktur',
                               'Penerbitan izin',
                           ][$i] : '') }}"
                           placeholder="Tahap {{ $i + 1 }} (kosongkan bila tidak dipakai)"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @endfor
            </div>
            <p class="mt-1 text-xs text-gray-500">
                Nama tahap ini dibaca member di portal PLD dan ikut ke isi notifikasi kenaikan tahap.
            </p>
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

@extends('layouts.app')

@section('title', 'Jalur B — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Jalur B — Publish notifikasi</h1>
    <p class="mt-0.5 mb-5 max-w-3xl text-sm leading-relaxed text-gray-500">
        Aplikasi ini memanggil PLD. Dipakai untuk pemberitahuan <strong>di luar</strong> proses bertahap:
        pengumuman, akun ditangguhkan, undangan. Untuk perubahan status permohonan
        <strong>jangan pakai jalur ini</strong> — Jalur A sudah menerbitkan notifikasinya sendiri,
        dan memakai keduanya untuk peristiwa yang sama menghasilkan notifikasi ganda.
    </p>

    @unless ($configured)
        <div class="mb-5 rounded-lg border-l-4 border-amber-400 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">
            <p class="font-semibold">Jalur B belum dikonfigurasi.</p>
            <p class="mt-1">
                Isi <span class="font-mono text-xs">PLD_BASE_URL</span> dan
                <span class="font-mono text-xs">PLD_SERVICE_KEY</span> di <span class="font-mono text-xs">.env</span>.
                Service Key diterbitkan sendiri oleh pengelola layanan dari panel
                <em>Service Key (Notifikasi)</em> di halaman Aplikasi backoffice PLD —
                nilai penuhnya hanya tampil sekali saat dibuat.
            </p>
        </div>
    @endunless

    <form method="POST" action="{{ route('admin.publish.store') }}"
          class="max-w-2xl space-y-4 rounded-xl border border-gray-200 bg-white p-6">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email member di PLD</label>
            <input id="email" name="email" type="email" required maxlength="190" value="{{ old('email') }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-gray-500">
                Dipakai PLD untuk <strong>mencari</strong> member — bukan sebagai alamat tujuan.
                Alamat kirim selalu diambil PLD dari datanya sendiri.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="event_type" class="mb-1 block text-sm font-medium text-gray-700">eventType</label>
                <input id="event_type" name="event_type" required maxlength="64"
                       value="{{ old('event_type', 'PENGUMUMAN_PEMELIHARAAN') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs">
                <p class="mt-1 text-xs text-gray-500">Label milik Anda. PLD tidak menafsirkannya.</p>
            </div>

            <div>
                <label for="action_label" class="mb-1 block text-sm font-medium text-gray-700">
                    actionLabel <span class="font-normal text-gray-400">(opsional)</span>
                </label>
                <input id="action_label" name="action_label" maxlength="40" value="{{ old('action_label') }}"
                       placeholder="Lihat Pengumuman"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label for="title" class="mb-1 block text-sm font-medium text-gray-700">
                Judul <span class="font-normal text-gray-400">(maks 150)</span>
            </label>
            <input id="title" name="title" required maxlength="150" value="{{ old('title') }}"
                   placeholder="Pemeliharaan Terjadwal Aplikasi PEL"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="message" class="mb-1 block text-sm font-medium text-gray-700">
                Isi <span class="font-normal text-gray-400">(maks 2000)</span>
            </label>
            <textarea id="message" name="message" required maxlength="2000" rows="4"
                      placeholder="Aplikasi PEL tidak dapat diakses pada 3 Agustus 2026 pukul 22.00-24.00 WIB."
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
        </div>

        <div>
            <label for="action_path" class="mb-1 block text-sm font-medium text-gray-700">
                actionPath <span class="font-normal text-gray-400">(opsional)</span>
            </label>
            <input id="action_path" name="action_path" maxlength="500" value="{{ old('action_path') }}"
                   placeholder="/pengumuman/2026-08-03"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs">
            <p class="mt-1 text-xs text-amber-700">
                <strong>PATH RELATIF</strong>, bukan URL. Host-nya disusun PLD dari Redirect URL terdaftar —
                kalau host boleh datang dari kita, email berkop AviasiHub bisa menaut ke domain mana pun.
            </p>
        </div>

        <button type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
            Kirim ke PLD
        </button>
    </form>
@endsection

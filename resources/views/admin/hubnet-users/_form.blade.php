{{-- $user: HubnetUser|null · $types: HubnetUserType[] · $action: url · $method: 'POST'|'PUT' --}}
@php($u = $user ?? null)

<form method="POST" action="{{ $action }}"
      class="max-w-2xl space-y-5 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @if (($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="type" class="mb-1 block text-sm font-medium text-gray-700">Tipe (radio di halaman login)</label>
            <select id="type" name="type" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected((int) old('type', $u?->type->value ?? 1) === $type->value)>
                        {{ $type->radioLabel() }} — login pakai {{ $type->usernameHint() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
            <input id="username" name="username" required maxlength="190" value="{{ old('username', $u?->username) }}"
                   placeholder="NIP / NIB / email"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            <p class="mt-1 text-xs text-gray-500">Nilai yang diketik di kolom USERNAME saat login.</p>
        </div>
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">
            Kata sandi @if ($u)<span class="font-normal text-gray-400">(kosongkan = biarkan seperti semula)</span>@endif
        </label>
        <input id="password" name="password" type="text" maxlength="190" autocomplete="off"
               placeholder="{{ $u ? '••••••• (tidak berubah bila kosong)' : 'mis. hubnet123' }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        <p class="mt-1 text-xs text-gray-500">Ditampilkan sebagai teks — ini akun uji dummy, bukan kredensial nyata.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
            <input id="name" name="name" required maxlength="190" value="{{ old('name', $u?->name) }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" maxlength="190" value="{{ old('email', $u?->email) }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
    </div>

    <fieldset class="grid gap-4 rounded-lg border border-gray-200 p-4 sm:grid-cols-2">
        <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
            Identitas & atribut (isi seperlunya menurut tipe)
        </legend>

        @foreach ([
            'nip' => 'NIP (pegawai)',
            'nik' => 'NIK (kunci pengaitan akun di PLD)',
            'npwp' => 'NPWP (OSS)',
            'phone' => 'Telepon',
            'unit' => 'Unit kerja (pegawai)',
            'kode_unit' => 'Kode unit (005000000000000 = DJPU → layak admin)',
            'golongan' => 'Golongan',
            'pangkat' => 'Pangkat',
            'jabatan_fungsional' => 'Jabatan fungsional',
            'jabatan_struktural' => 'Jabatan struktural',
        ] as $field => $label)
            <div>
                <label for="{{ $field }}" class="mb-1 block text-xs text-gray-600">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" maxlength="190" value="{{ old($field, $u?->{$field}) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        @endforeach
    </fieldset>

    <fieldset class="space-y-2 rounded-lg border border-gray-200 p-4">
        <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Penanda Hubnet (B5)</legend>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="status" value="1" @checked((bool) old('status', $u?->status ?? true)) class="rounded border-gray-300">
            Aktif <span class="text-xs text-gray-400">(hilangkan centang → pld-user menolak dengan B5)</span>
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_deleted" value="1" @checked((bool) old('is_deleted', $u?->is_deleted ?? false)) class="rounded border-gray-300">
            Ditandai terhapus <span class="text-xs text-gray-400">(juga memblokir login — B5)</span>
        </label>
    </fieldset>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Simpan
        </button>
        <a href="{{ route('admin.hubnet-users.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
    </div>
</form>

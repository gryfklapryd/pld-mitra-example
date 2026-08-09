{{-- $member: Member|null · $action: url · $method: 'POST'|'PUT' --}}
@php($m = $member ?? null)

<form method="POST" action="{{ $action }}"
      class="max-w-xl space-y-5 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @if (($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="user_login" class="mb-1 block text-sm font-medium text-gray-700">user_login</label>
        <input id="user_login" name="user_login" required maxlength="100" value="{{ old('user_login', $m?->user_login) }}"
               autocomplete="off"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        <p class="mt-1 text-xs text-gray-500">
            Nilai yang diketik member saat menautkan akun di PLD, dan yang dikirim PLD pada
            <span class="font-mono">userLogins[]</span>. Unik tanpa memandang besar-kecil huruf.
        </p>
    </div>

    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
        <input id="name" name="name" required maxlength="150" value="{{ old('name', $m?->name) }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email <span class="font-normal text-gray-400">(opsional)</span></label>
        <input id="email" name="email" type="email" maxlength="190" value="{{ old('email', $m?->email) }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">
            Kata sandi @if ($m)<span class="font-normal text-gray-400">(kosongkan = biarkan seperti semula)</span>@endif
        </label>
        <input id="password" name="password" type="password" autocomplete="new-password"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter. Inilah yang diverifikasi PLD lewat <span class="font-mono">API User Validation URL</span>.</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $m?->is_active ?? true)) class="rounded border-gray-300">
        Aktif <span class="text-xs text-gray-400">(nonaktif → tidak ikut disinkronkan & login ditolak)</span>
    </label>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
        <a href="{{ route('admin.members.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
    </div>
</form>

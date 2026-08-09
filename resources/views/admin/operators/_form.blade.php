{{-- $operator: User|null · $action: url · $method: 'POST'|'PUT' --}}
@php($o = $operator ?? null)

<form method="POST" action="{{ $action }}"
      class="max-w-xl space-y-5 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @if (($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
        <input id="name" name="name" required maxlength="255" value="{{ old('name', $o?->name) }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email (untuk masuk)</label>
        <input id="email" name="email" type="email" required maxlength="190" value="{{ old('email', $o?->email) }}"
               autocomplete="off"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">
            Kata sandi @if ($o)<span class="font-normal text-gray-400">(kosongkan = biarkan seperti semula)</span>@endif
        </label>
        <input id="password" name="password" type="password" autocomplete="new-password"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        <p class="mt-1 text-xs text-amber-700">
            Minimal 12 karakter — akun ini membuka panel yang bisa mengirim email berkop resmi atas nama layanan.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
        <a href="{{ route('admin.operators.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
    </div>
</form>

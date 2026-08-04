@extends('layouts.app')

@section('title', 'Masuk — PEL')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h1 class="text-lg font-bold text-gray-800">Masuk ke PEL</h1>
            <p class="mt-1 mb-5 text-sm leading-relaxed text-gray-500">
                Gunakan akun PEL Anda. Kredensial yang sama inilah yang Anda ketikkan
                di portal PLD saat menautkan akun.
            </p>

            <form method="POST" action="{{ route('masuk') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="user_login" class="mb-1 block text-sm font-medium text-gray-700">Nama pengguna</label>
                    <input id="user_login" name="user_login" required autofocus maxlength="100"
                           value="{{ old('user_login') }}" autocomplete="username"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-gray-300">
                    Ingat saya
                </label>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-xs leading-relaxed text-gray-500">
            Datang dari portal PLD? Anda tidak perlu mengetik apa pun di sini —
            tekan layanan PEL di portal, dan Anda akan masuk otomatis lewat SSO.
        </p>

        @if (app()->environment('local'))
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-4 text-xs text-gray-500">
                <p class="mb-1 font-semibold text-gray-600">Akun contoh (hanya tampil di environment lokal)</p>
                <p><span class="font-mono">john_doe</span> / <span class="font-mono">rahasia123</span></p>
                <p><span class="font-mono">siti_aminah</span> / <span class="font-mono">rahasia123</span></p>
            </div>
        @endif
    </div>
@endsection

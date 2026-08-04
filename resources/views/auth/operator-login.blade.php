@extends('layouts.app')

@section('title', 'Masuk Operator — PEL')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h1 class="text-lg font-bold text-gray-800">Panel Operator</h1>
            <p class="mt-1 mb-5 text-sm leading-relaxed text-gray-500">
                Khusus pengelola proses di aplikasi ini. Member layanan
                <a href="{{ route('masuk') }}" class="text-blue-700 hover:underline">masuk di sini</a>.
            </p>

            <form method="POST" action="{{ route('operator.masuk') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" required autofocus maxlength="190"
                           value="{{ old('email') }}" autocomplete="username"
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
                        class="w-full rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                    Masuk
                </button>
            </form>
        </div>

        @if (app()->environment('local'))
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-4 text-xs text-gray-500">
                <p class="mb-1 font-semibold text-gray-600">Akun contoh (hanya tampil di environment lokal)</p>
                <p><span class="font-mono">operator@pel.test</span> / <span class="font-mono">rahasia123</span></p>
            </div>
        @endif
    </div>
@endsection

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PEL — Mitra PLD')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gray-50 text-gray-800 antialiased">

@php
    $member = auth()->guard('member')->user();
    $operator = auth()->guard('web')->user();
@endphp

<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2">
            <span class="rounded-md bg-blue-600 px-2 py-1 text-xs font-bold tracking-wide text-white">PEL</span>
            <span class="text-sm font-semibold text-gray-700">Perizinan Elektronik <span class="font-normal text-gray-400">— aplikasi mitra PLD</span></span>
        </a>

        <nav class="flex flex-wrap items-center gap-1 text-sm">
            @if ($operator)
                <a href="{{ route('admin.applications.index') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.applications.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Permohonan
                </a>
                <a href="{{ route('admin.logs.index') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.logs.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Log Integrasi
                </a>
                <a href="{{ route('admin.publish.create') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.publish.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Jalur B
                </a>
                <a href="{{ route('admin.members.index') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.members.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Member
                </a>
                <a href="{{ route('admin.operators.index') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.operators.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Operator
                </a>
                <a href="{{ route('admin.hubnet-users.index') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.hubnet-users.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Hubnet: User
                </a>
                <a href="{{ route('admin.hubnet-clients.index') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.hubnet-clients.*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Hubnet: Klien
                </a>

                <span class="ml-2 border-l border-gray-200 pl-3 text-xs text-gray-500">
                    Operator: <span class="font-medium text-gray-700">{{ $operator->name }}</span>
                </span>
                <form method="POST" action="{{ route('operator.keluar') }}" class="ml-1">
                    @csrf
                    <button type="submit" class="rounded-md px-3 py-1.5 text-gray-600 hover:bg-gray-100">Keluar</button>
                </form>
            @elseif ($member)
                <span class="text-xs text-gray-500">
                    Masuk sebagai <span class="font-medium text-gray-700">{{ $member->name }}</span>
                    <span class="font-mono text-gray-400">({{ $member->user_login }})</span>
                </span>
                <form method="POST" action="{{ route('keluar') }}" class="ml-1">
                    @csrf
                    <button type="submit" class="rounded-md px-3 py-1.5 text-gray-600 hover:bg-gray-100">Keluar</button>
                </form>
            @else
                <a href="{{ route('masuk') }}"
                   class="rounded-md px-3 py-1.5 {{ request()->routeIs('masuk') ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                    Masuk
                </a>
                <a href="{{ route('operator.masuk') }}"
                   class="rounded-md px-3 py-1.5 text-gray-500 hover:bg-gray-100">
                    Operator
                </a>
            @endif
        </nav>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-6">
    @if (session('status'))
        <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm leading-relaxed text-green-900">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm leading-relaxed text-red-900">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-900">
            <p class="mb-1 font-semibold">Periksa kembali isian berikut:</p>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="mx-auto max-w-6xl px-4 pb-10 pt-4 text-xs leading-relaxed text-gray-400">
    Aplikasi contoh implementasi kontrak integrasi PLD v1.1 (tracking
    <span class="font-mono">{{ config('pld.contract_version') }}</span>).
</footer>

</body>
</html>

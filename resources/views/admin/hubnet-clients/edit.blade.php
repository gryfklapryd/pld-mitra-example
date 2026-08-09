@extends('layouts.app')

@section('title', 'Ubah klien SSO — PEL')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800">Ubah klien: {{ $client->name }}</h1>
        <form method="POST" action="{{ route('admin.hubnet-clients.regenerate', $client) }}"
              onsubmit="return confirm('Bangkitkan ulang secret? Aplikasi klien harus memperbarui env-nya.')">
            @csrf
            <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">
                Bangkitkan ulang secret
            </button>
        </form>
    </div>

    @include('admin.hubnet-clients._form', ['client' => $client, 'action' => route('admin.hubnet-clients.update', $client), 'method' => 'PUT'])
@endsection

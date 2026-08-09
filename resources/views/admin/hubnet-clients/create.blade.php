@extends('layouts.app')

@section('title', 'Daftar klien SSO — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Daftarkan klien SSO baru</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">
        <span class="font-mono">client_id</span> dan <span class="font-mono">client_secret</span> dibangkitkan
        otomatis saat disimpan, lalu ditampilkan agar bisa disalin ke env aplikasi klien.
    </p>

    @include('admin.hubnet-clients._form', ['client' => null, 'action' => route('admin.hubnet-clients.store'), 'method' => 'POST'])
@endsection

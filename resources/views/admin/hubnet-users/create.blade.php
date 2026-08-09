@extends('layouts.app')

@section('title', 'Buat identitas Hubnet — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Buat identitas Hubnet (dummy)</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">
        Identitas ini menghuni Hubnet TIRUAN — "basis data Pusdatin" palsu. Ia
        muncul di halaman login SSO dan diklaim pld-user saat menukar token.
    </p>

    @include('admin.hubnet-users._form', ['user' => null, 'action' => route('admin.hubnet-users.store'), 'method' => 'POST'])
@endsection

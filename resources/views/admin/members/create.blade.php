@extends('layouts.app')

@section('title', 'Buat member — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Buat member layanan</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">
        Menggantikan <span class="font-mono">php artisan pel:member</span>.
    </p>

    @include('admin.members._form', ['member' => null, 'action' => route('admin.members.store'), 'method' => 'POST'])
@endsection

@extends('layouts.app')

@section('title', 'Buat operator — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Buat operator internal</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">
        Menggantikan <span class="font-mono">php artisan pel:operator</span>. Akun untuk masuk ke <span class="font-mono">/operator/masuk</span>.
    </p>

    @include('admin.operators._form', ['operator' => null, 'action' => route('admin.operators.store'), 'method' => 'POST'])
@endsection

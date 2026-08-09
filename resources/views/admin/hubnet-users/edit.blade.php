@extends('layouts.app')

@section('title', 'Ubah identitas Hubnet — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Ubah identitas Hubnet</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">
        <span class="font-mono">{{ $user->type->radioLabel() }}</span> ·
        <span class="font-mono">{{ $user->username }}</span>
    </p>

    @include('admin.hubnet-users._form', ['user' => $user, 'action' => route('admin.hubnet-users.update', $user), 'method' => 'PUT'])
@endsection

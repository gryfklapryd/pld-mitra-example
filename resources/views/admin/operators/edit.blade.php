@extends('layouts.app')

@section('title', 'Ubah operator — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Ubah operator</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500">{{ $operator->email }}</p>

    @include('admin.operators._form', ['operator' => $operator, 'action' => route('admin.operators.update', $operator), 'method' => 'PUT'])
@endsection

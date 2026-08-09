@extends('layouts.app')

@section('title', 'Ubah member — PEL')

@section('content')
    <h1 class="text-xl font-bold text-gray-800">Ubah member</h1>
    <p class="mt-0.5 mb-5 text-sm text-gray-500"><span class="font-mono">{{ $member->user_login }}</span></p>

    @include('admin.members._form', ['member' => $member, 'action' => route('admin.members.update', $member), 'method' => 'PUT'])
@endsection

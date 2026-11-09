@extends('layouts.app')

@section('title', 'Ubah pengguna')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="hover:underline">Pengguna</a> / Ubah
@endsection

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Ubah pengguna</h1>
    @include('admin.users._form')
@endsection

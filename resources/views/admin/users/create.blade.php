@extends('layouts.app')

@section('title', 'Tambah pengguna')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="hover:underline">Pengguna</a> / Tambah
@endsection

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Tambah pengguna</h1>
    @include('admin.users._form')
@endsection

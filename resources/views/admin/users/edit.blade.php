@extends('layouts.app')

@section('title', 'Ubah pengguna')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="hover:underline">Pengguna</a> / Ubah
@endsection

@section('content')
    <div class="page-head"><div><h1>Ubah pengguna</h1><p>{{ $user->email }}</p></div></div>
    @include('admin.users._form')
@endsection

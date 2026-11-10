@extends('layouts.app')

@section('title', 'Tambah pengguna')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="hover:underline">Pengguna</a> / Tambah
@endsection

@section('content')
    <div class="page-head"><div><h1>Tambah pengguna</h1><p>Buat akun untuk Event Admin atau Resepsionis.</p></div></div>
    @include('admin.users._form')
@endsection

@extends('layouts.app')

@section('title', 'Tambah acara')
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> / Tambah
@endsection

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Tambah acara</h1>
    @include('admin.events._form')
@endsection

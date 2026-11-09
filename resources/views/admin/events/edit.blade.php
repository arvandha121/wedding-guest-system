@extends('layouts.app')

@section('title', 'Ubah acara')
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> /
    <a href="{{ route('admin.events.show', $event) }}" class="hover:underline">{{ $event->name }}</a> / Ubah
@endsection

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Ubah acara</h1>
    @include('admin.events._form')
@endsection

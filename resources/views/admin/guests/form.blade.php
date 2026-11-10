@extends('layouts.app')

@php $isEdit = $guest->exists; @endphp

@section('title', $isEdit ? 'Ubah tamu' : 'Tambah tamu')
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> /
    <a href="{{ route('admin.events.guests.index', $event) }}" class="hover:underline">Tamu</a> / {{ $isEdit ? 'Ubah' : 'Tambah' }}
@endsection

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ $isEdit ? 'Ubah tamu' : 'Tambah tamu' }}</h1>

    <form method="POST" action="{{ $isEdit ? route('admin.guests.update', $guest) : route('admin.events.guests.store', $event) }}" class="grid max-w-3xl gap-4 sm:grid-cols-2" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-field name="name" label="Nama tamu" :value="$guest->name" required />
        <x-field name="family_name" label="Nama keluarga" :value="$guest->family_name" />
        <x-field name="phone" type="tel" label="Telepon / WhatsApp" :value="$guest->phone" />
        <x-field name="email" type="email" label="Email" :value="$guest->email" />
        <x-select name="category" label="Kategori" placeholder="Tanpa kategori" :value="$guest->category?->ulid" :options="$categories->pluck('name', 'ulid')->all()" />
        <x-field name="quota" type="number" label="Kuota undangan" :value="$guest->quota ?? 1" hint="Jumlah orang yang boleh hadir (1–100)." />
        <x-field name="organization" label="Instansi" :value="$guest->organization" />
        <x-field name="address" label="Alamat" :value="$guest->address" />
        <x-textarea name="notes" label="Catatan internal" :value="$guest->notes" class="sm:col-span-2" />

        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="rounded bg-accent px-5 py-2.5 text-sm font-medium text-white hover:bg-accent-dark">{{ $isEdit ? 'Simpan perubahan' : 'Tambah tamu' }}</button>
            <a href="{{ route('admin.events.guests.index', $event) }}" class="text-sm text-muted hover:underline">Batal</a>
        </div>
    </form>
@endsection

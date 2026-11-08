@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold">Selamat datang, {{ $user->name }}</h1>
    <p class="mt-1 text-sm text-muted">Modul acara, tamu, undangan, dan laporan akan tersedia pada phase berikutnya.</p>

    <section class="mt-8 rounded border border-line bg-white" aria-labelledby="akun">
        <h2 id="akun" class="border-b border-line px-5 py-3 text-sm font-medium">Akun Anda</h2>
        <dl class="divide-y divide-line text-sm">
            <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:gap-6">
                <dt class="w-40 text-muted">Nama</dt>
                <dd>{{ $user->name }}</dd>
            </div>
            <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:gap-6">
                <dt class="w-40 text-muted">Email</dt>
                <dd>{{ $user->email }}</dd>
            </div>
            <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:gap-6">
                <dt class="w-40 text-muted">Peran</dt>
                <dd>{{ $user->role->label() }}</dd>
            </div>
            <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:gap-6">
                <dt class="w-40 text-muted">Login terakhir</dt>
                <dd>{{ $lastLogin ?? '-' }}</dd>
            </div>
        </dl>
    </section>
@endsection

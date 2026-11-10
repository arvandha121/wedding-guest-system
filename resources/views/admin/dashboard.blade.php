@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    @php
        $stats = [
            ['Acara aktif', $events->count(), 'ic-cal'],
            ['Berlangsung', $events->where('status.value', 'ongoing')->count(), 'ic-live'],
            ['Dipublikasikan', $events->where('status.value', 'published')->count(), 'ic-pub'],
            ['Draft', $events->where('status.value', 'draft')->count(), 'ic-draft'],
        ];
    @endphp

    <section class="hero">
        <div>
            <p class="hero-eyebrow">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1>Selamat datang, {{ $user->name }} 👋</h1>
            <p class="hero-sub">Pantau acara yang sedang berjalan dan kelola tamu serta undangan dari satu tempat.</p>
        </div>
        <a href="{{ route('admin.events.index') }}" class="hero-btn">Lihat semua acara →</a>
    </section>

    <div class="stat-grid">
        @foreach ($stats as [$label, $count, $cls])
            <div class="stat {{ $cls }}">
                <span class="stat-dot"></span>
                <p class="stat-num">{{ $count }}</p>
                <p class="stat-label">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="dash-grid">
        <section class="card">
            <h2 class="card-title">Acara mendatang</h2>
            @forelse ($events as $event)
                <a href="{{ route('admin.events.show', $event) }}" class="dv-row">
                    <span class="dv-date"><b>{{ $event->event_date->format('d') }}</b>{{ $event->event_date->translatedFormat('M') }}</span>
                    <span class="dv-info">
                        <strong>{{ $event->name }}</strong>
                        <small>{{ $event->venue_name }}</small>
                    </span>
                    <x-status-badge :status="$event->status" />
                </a>
            @empty
                <p class="empty">Belum ada acara yang ditugaskan kepada Anda.</p>
            @endforelse
        </section>

        <section class="card">
            <h2 class="card-title">Akun Anda</h2>
            <div class="acct">
                <span class="wgs-avatar lg">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <strong>{{ $user->name }}</strong>
                <small>{{ $user->email }}</small>
                <span class="role-pill {{ $user->role->value }}">{{ $user->role->label() }}</span>
            </div>
            <p class="acct-foot">Login terakhir <b>{{ $lastLogin ?? '-' }}</b></p>
        </section>
    </div>
@endsection

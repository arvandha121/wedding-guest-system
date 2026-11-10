@extends('layouts.app')

@section('title', 'Acara')
@section('breadcrumb', 'Acara')

@section('content')
    <div class="page-head">
        <div><h1>Acara</h1><p>Kelola acara, sesi, tamu, dan undangan.</p></div>
        @can('create', \App\Models\Event::class)
            <a href="{{ route('admin.events.create') }}" class="bg-accent" style="padding:.65rem 1.2rem;font-size:.9rem">+ Tambah acara</a>
        @endcan
    </div>

    <form method="GET" class="card filter-card">
        <div>
            <label for="search" class="block text-sm font-medium">Cari</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama acara atau mempelai">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="btns">
            <button type="submit" class="btn-ghost" style="padding:.65rem 1.1rem;font-size:.88rem">Terapkan</button>
            <a href="{{ route('admin.events.index') }}" class="link-cancel">Atur ulang</a>
        </div>
    </form>

    @if ($events->isEmpty())
        <div class="card empty" style="margin-top:1.25rem">Belum ada acara yang cocok.</div>
    @else
        <div class="ac-list">
            @foreach ($events as $event)
                <a href="{{ route('admin.events.show', $event) }}" class="ac-row">
                    <span class="dv-date"><b>{{ $event->event_date->format('d') }}</b>{{ $event->event_date->translatedFormat('M') }}<i>{{ $event->event_date->format('Y') }}</i></span>
                    <span class="ac-main">
                        <strong>{{ $event->name }}</strong>
                        <small>{{ $event->groom_name }} &amp; {{ $event->bride_name }}</small>
                    </span>
                    <span class="ac-cell"><em>Lokasi</em>{{ $event->venue_name }}</span>
                    <span class="ac-cell"><em>Sesi</em>{{ $event->sessions_count }} sesi</span>
                    <x-status-badge :status="$event->status" />
                    <span class="ac-go" aria-hidden="true">→</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-4">{{ $events->links() }}</div>
@endsection

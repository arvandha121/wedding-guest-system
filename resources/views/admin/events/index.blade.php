@extends('layouts.app')

@section('title', 'Acara')
@section('breadcrumb', 'Acara')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Acara</h1>
        @can('create', \App\Models\Event::class)
            <a href="{{ route('admin.events.create') }}" class="rounded bg-accent px-4 py-2 text-sm font-medium text-white hover:bg-accent-dark">Tambah acara</a>
        @endcan
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded border border-line bg-white p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="search" class="block text-sm font-medium">Cari</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama acara atau mempelai"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium">Status</label>
            <select id="status" name="status" class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Terapkan</button>
            <a href="{{ route('admin.events.index') }}" class="px-2 py-2 text-sm text-muted hover:underline">Atur ulang</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded border border-line bg-white">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="text-left text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Acara</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Lokasi</th>
                    <th class="px-4 py-3 font-medium">Sesi</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($events as $event)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.events.show', $event) }}" class="font-medium text-accent hover:underline">{{ $event->name }}</a>
                            <p class="text-muted">{{ $event->groom_name }} &amp; {{ $event->bride_name }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $event->event_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $event->venue_name }}</td>
                        <td class="px-4 py-3">{{ $event->sessions_count }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$event->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-muted">Belum ada acara yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $events->links() }}</div>
@endsection

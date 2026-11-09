@extends('layouts.app')

@section('title', $event->name)
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> / {{ $event->name }}
@endsection

@section('content')
@php
    $tz = config('wgs.display_timezone');
    $rows = [
            'Judul undangan' => $event->title,
            'Tanggal' => $event->event_date->format('d/m/Y') . ' · ' . substr((string) $event->start_time, 0, 5) . '–' . substr((string) $event->end_time, 0, 5),
            'Lokasi' => $event->venue_name . ' — ' . $event->venue_address,
            'Mode check-in' => $event->checkin_mode->label(),
            'Check-in manual' => $event->allow_manual_checkin ? 'Diizinkan' : 'Tidak diizinkan',
            'Jendela check-in' => ($event->checkin_opens_at?->timezone($tz)->format('d/m/Y H:i') ?? '-') . ' sampai ' . ($event->checkin_closes_at?->timezone($tz)->format('d/m/Y H:i') ?? '-'),
            'Tema' => ucfirst($event->theme),
            'Kontak' => trim(($event->contact_name ?? '') . ' ' . ($event->contact_phone ?? '')) ?: '-',
        ];
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold">{{ $event->name }}</h1>
            <x-status-badge :status="$event->status" />
        </div>
        <p class="mt-1 text-sm text-muted">{{ $event->groom_name }} &amp; {{ $event->bride_name }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if ($canUpdate)
            <a href="{{ route('admin.events.edit', $event) }}" class="rounded border border-line px-3 py-1.5 text-sm hover:bg-paper">Ubah</a>
            @foreach ($event->status->allowedTransitions() as $target)
                <form method="POST" action="{{ route('admin.events.status', $event) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $target->value }}">
                    <button type="submit" class="rounded border border-line px-3 py-1.5 text-sm hover:bg-paper">{{ $target->label() }}</button>
                </form>
            @endforeach
        @endif
        @if ($canDelete)
            <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                  onsubmit="return confirm('Arsipkan acara ini? Acara dapat dipulihkan oleh Super Admin.')">
                @csrf @method('DELETE')
                <button type="submit" class="rounded border border-red-200 px-3 py-1.5 text-sm text-danger hover:bg-red-50">Arsipkan</button>
            </form>
        @endif
    </div>
</div>

<section class="mt-8 rounded border border-line bg-white" aria-labelledby="ringkasan">
    <h2 id="ringkasan" class="border-b border-line px-5 py-3 text-sm font-medium">Ringkasan</h2>
    <dl class="divide-y divide-line text-sm">
        @foreach ($rows as $label => $value)
            <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:gap-6">
                <dt class="w-44 shrink-0 text-muted">{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
        @if ($event->google_maps_url)
            <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:gap-6">
                <dt class="w-44 shrink-0 text-muted">Google Maps</dt>
                <dd><a href="{{ $event->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline">Buka peta</a></dd>
            </div>
        @endif
    </dl>
</section>

{{-- Sesi --}}
<section class="mt-8 rounded border border-line bg-white" aria-labelledby="sesi" id="bagian-sesi">
    <h2 id="sesi" class="border-b border-line px-5 py-3 text-sm font-medium">Sesi acara</h2>

    @forelse ($event->sessions as $session)
        <div class="border-b border-line px-5 py-3 text-sm">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">{{ $session->name }}</p>
                    <p class="text-muted">{{ $session->start_time->timezone($tz)->format('d/m/Y H:i') }} – {{ $session->end_time->timezone($tz)->format('H:i') }}</p>
                </div>
                <x-status-badge :status="$session->status" />
            </div>

            @if ($canUpdate)
                <details class="mt-2">
                    <summary class="cursor-pointer text-accent hover:underline">Ubah atau hapus</summary>
                    <form method="POST" action="{{ route('admin.sessions.update', $session) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-sm font-medium">Nama sesi</label>
                            <input name="name" value="{{ $session->name }}" required class="mt-1 block w-full rounded border border-line px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="status" class="mt-1 block w-full rounded border border-line px-3 py-2 text-sm">
                                @foreach (\App\Enums\SessionStatus::cases() as $s)
                                    <option value="{{ $s->value }}" @selected($session->status === $s)>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Mulai</label>
                            <input type="datetime-local" name="start_time" value="{{ $session->start_time->timezone($tz)->format('Y-m-d\TH:i') }}" required class="mt-1 block w-full rounded border border-line px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Selesai</label>
                            <input type="datetime-local" name="end_time" value="{{ $session->end_time->timezone($tz)->format('Y-m-d\TH:i') }}" required class="mt-1 block w-full rounded border border-line px-3 py-2 text-sm">
                        </div>
                        <div class="sm:col-span-2 flex gap-2">
                            <button type="submit" class="rounded bg-accent px-3 py-1.5 text-sm font-medium text-white hover:bg-accent-dark">Simpan</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}" class="mt-2"
                          onsubmit="return confirm('Hapus sesi ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-danger hover:underline">Hapus sesi</button>
                    </form>
                </details>
            @endif
        </div>
    @empty
        <p class="px-5 py-6 text-sm text-muted">Belum ada sesi. Tambahkan minimal satu sesi sebelum mempublikasikan acara.</p>
    @endforelse

    @if ($canUpdate)
        <form method="POST" action="{{ route('admin.sessions.store', $event) }}" class="grid gap-3 px-5 py-4 sm:grid-cols-4">
            @csrf
            <x-field name="name" label="Nama sesi" required class="sm:col-span-2" />
            <x-field name="start_time" type="datetime-local" label="Mulai" required />
            <x-field name="end_time" type="datetime-local" label="Selesai" required />
            <div class="sm:col-span-4">
                <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Tambah sesi</button>
            </div>
        </form>
    @endif
</section>

{{-- Penugasan --}}
@if ($canUpdate)
<section class="mt-8 rounded border border-line bg-white" aria-labelledby="tim">
    <h2 id="tim" class="border-b border-line px-5 py-3 text-sm font-medium">Penugasan</h2>

    <div class="border-b border-line px-5 py-4">
        <h3 class="text-sm font-medium">Event Admin</h3>
        <ul class="mt-2 divide-y divide-line text-sm">
            @forelse ($admins as $admin)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $admin->name }} <span class="text-muted">({{ $admin->email }})</span></span>
                    @if ($canAssignAdmins)
                        <form method="POST" action="{{ route('admin.members.destroy', [$event, $admin]) }}" onsubmit="return confirm('Cabut penugasan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-danger hover:underline">Cabut</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-2 text-muted">Belum ada Event Admin.</li>
            @endforelse
        </ul>
        @if ($canAssignAdmins)
            <form method="POST" action="{{ route('admin.members.store', $event) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                <x-select name="user" label="Tugaskan Event Admin" :options="$assignableAdmins->mapWithKeys(fn ($u) => [$u->ulid => $u->name . ' (' . $u->email . ')'])->all()" placeholder="Pilih pengguna" class="sm:w-80" />
                <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Tugaskan</button>
            </form>
        @endif
    </div>

    <div class="px-5 py-4">
        <h3 class="text-sm font-medium">Resepsionis</h3>
        <ul class="mt-2 divide-y divide-line text-sm">
            @forelse ($receptionists as $r)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $r->name }} <span class="text-muted">({{ $r->email }})</span></span>
                    <form method="POST" action="{{ route('admin.members.destroy', [$event, $r]) }}" onsubmit="return confirm('Cabut penugasan ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-danger hover:underline">Cabut</button>
                    </form>
                </li>
            @empty
                <li class="py-2 text-muted">Belum ada resepsionis.</li>
            @endforelse
        </ul>

        @if ($assignableReceptionists->isNotEmpty())
            <form method="POST" action="{{ route('admin.members.store', $event) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                <x-select name="user" label="Tugaskan resepsionis yang sudah ada" :options="$assignableReceptionists->mapWithKeys(fn ($u) => [$u->ulid => $u->name . ' (' . $u->email . ')'])->all()" placeholder="Pilih resepsionis" class="sm:w-80" />
                <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Tugaskan</button>
            </form>
        @endif

        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-accent hover:underline">Buat akun resepsionis baru</summary>
            <form method="POST" action="{{ route('admin.receptionists.store', $event) }}" class="mt-3 grid gap-3 sm:grid-cols-3">
                @csrf
                <x-field name="name" label="Nama" required />
                <x-field name="email" type="email" label="Email" required autocomplete="off" />
                <x-field name="password" type="password" label="Kata sandi" required autocomplete="new-password" hint="Minimal 10 karakter, huruf besar dan kecil, serta angka." />
                <div class="sm:col-span-3">
                    <button type="submit" class="rounded bg-accent px-4 py-2 text-sm font-medium text-white hover:bg-accent-dark">Buat dan tugaskan</button>
                </div>
            </form>
        </details>
    </div>
</section>
@endif
@endsection

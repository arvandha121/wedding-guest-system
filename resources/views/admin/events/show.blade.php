@extends('layouts.app')

@section('title', $event->name)
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> / {{ $event->name }}
@endsection

@section('content')
@php
    $tz = config('wgs.display_timezone');
    $flow = ['draft', 'published', 'ongoing', 'completed'];
    $idx = array_search($event->status->value, $flow, true);
    $verb = ['published' => 'Publikasikan', 'draft' => 'Kembalikan ke Draft', 'ongoing' => 'Mulai acara', 'completed' => 'Tandai selesai', 'cancelled' => 'Batalkan acara'];
    $hint = [
        'draft' => 'Acara belum dipublikasikan. Publikasikan dulu sebelum mengirim undangan ke tamu.',
        'published' => 'Acara sudah dipublikasikan, undangan sudah dapat dikirim ke tamu.',
        'ongoing' => 'Acara sedang berlangsung.',
        'completed' => 'Acara sudah selesai.',
        'cancelled' => 'Acara dibatalkan. Kembalikan ke Draft bila ingin mengaktifkannya lagi.',
    ];
    $contact = trim(($event->contact_name ?? '') . ' ' . ($event->contact_phone ?? ''));
@endphp

{{-- Kepala halaman --}}
@php
    $ic = fn (string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
@endphp
<div class="ev-head ev-hero">
    <div>
        <div class="ev-title">
            <h1>{{ $event->name }}</h1>
            <x-status-badge :status="$event->status" />
        </div>
        <p class="ev-couple">{{ $event->groom_name }} &amp; {{ $event->bride_name }}</p>
        <div class="ev-chips">
            <span>{!! $ic('<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>') !!}{{ $event->event_date->locale('id')->translatedFormat('l, d F Y') }}</span>
            <span>{!! $ic('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>') !!}{{ substr((string) $event->start_time, 0, 5) }} – {{ substr((string) $event->end_time, 0, 5) }} WIB</span>
            <span>{!! $ic('<path d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>') !!}{{ $event->venue_name }}</span>
        </div>
    </div>
    @if ($canUpdate)
        <div class="ev-actions">
            <a href="{{ route('admin.events.guests.index', $event) }}" class="ev-btn primary">Kelola tamu</a>
            <a href="{{ route('admin.events.invitations.index', $event) }}" class="ev-btn primary">Kirim undangan</a>
            <a href="{{ route('admin.events.edit', $event) }}" class="ev-btn">Ubah acara</a>
        </div>
    @endif
</div>

@if ($event->status->value === 'cancelled')
    <div class="ev-note red">Acara ini berstatus <strong>Dibatalkan</strong>.</div>
@elseif ($event->sessions->isEmpty() && $event->status->value === 'draft')
    <div class="ev-note">Belum ada sesi. Tambahkan minimal satu sesi di bagian <a href="#bagian-sesi" class="text-accent hover:underline">Sesi acara</a> sebelum mempublikasikan.</div>
@endif

{{-- Status --}}
<section class="ev-card" aria-labelledby="status">
    <div class="ev-ch"><h2 id="status">Status acara</h2></div>
    <div class="ev-steps">
        @foreach ($flow as $i => $key)
            <div class="ev-step {{ $idx !== false && $i < $idx ? 'done' : '' }} {{ $idx === $i ? 'cur' : '' }}">
                <i>{{ $idx !== false && $i < $idx ? '✓' : $i + 1 }}</i>
                <span>{{ \App\Enums\EventStatus::from($key)->label() }}</span>
            </div>
        @endforeach
    </div>
    <div class="ev-state">
        <p>{{ $hint[$event->status->value] ?? '' }}</p>
        @if ($canUpdate)
            <div>
                @foreach ($event->status->allowedTransitions() as $target)
                    <form method="POST" action="{{ route('admin.events.status', $event) }}"
                          @if ($target->value === 'cancelled') onsubmit="return confirm('Batalkan acara ini?')" @endif>
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $target->value }}">
                        <button type="submit" class="ev-btn sm {{ $target->value === 'cancelled' ? 'danger' : ($target->value === 'published' ? 'primary' : '') }}">{{ $verb[$target->value] ?? $target->label() }}</button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- Ringkasan --}}
<section class="ev-card" aria-labelledby="ringkasan">
    <div class="ev-ch"><h2 id="ringkasan">Ringkasan</h2></div>
    <dl class="ev-info">
        <div>
            <dt>Tanggal &amp; waktu</dt>
            <dd>{{ $event->event_date->locale('id')->translatedFormat('l, d F Y') }}<small>{{ substr((string) $event->start_time, 0, 5) }} – {{ substr((string) $event->end_time, 0, 5) }} WIB</small></dd>
        </div>
        <div>
            <dt>Lokasi</dt>
            <dd>{{ $event->venue_name }}<small>{{ $event->venue_address }}</small>
                @if ($event->google_maps_url)<small><a href="{{ $event->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline">Buka di Google Maps ↗</a></small>@endif
            </dd>
        </div>
        <div>
            <dt>Check-in</dt>
            <dd>{{ $event->checkin_mode->label() }}
                <small>Manual: {{ $event->allow_manual_checkin ? 'diizinkan' : 'tidak diizinkan' }}</small>
                <small>Buka: {{ $event->checkin_opens_at?->timezone($tz)->format('d/m/Y H:i') ?? '-' }} · Tutup: {{ $event->checkin_closes_at?->timezone($tz)->format('d/m/Y H:i') ?? '-' }}</small>
            </dd>
        </div>
        <div><dt>Judul undangan</dt><dd>{{ $event->title }}</dd></div>
        <div><dt>Tema</dt><dd>{{ ucfirst($event->theme) }}</dd></div>
        <div><dt>Kontak</dt><dd>{{ $contact ?: '-' }}</dd></div>
    </dl>
</section>

{{-- Sesi --}}
<section class="ev-card" aria-labelledby="sesi" id="bagian-sesi">
    <div class="ev-ch"><h2 id="sesi">Sesi acara</h2><p>{{ $event->sessions->count() }} sesi</p></div>

    @forelse ($event->sessions as $session)
        <div class="ev-sess">
            <div class="ev-time"><b>{{ $session->start_time->timezone($tz)->format('H:i') }}</b><small>{{ $session->start_time->timezone($tz)->locale('id')->translatedFormat('d M Y') }}</small></div>
            <div class="ev-sbody">
                <div class="ev-srow">
                    <div>
                        <p class="n">{{ $session->name }}</p>
                        <p class="t">{{ $session->start_time->timezone($tz)->format('H:i') }} – {{ $session->end_time->timezone($tz)->format('H:i') }} WIB</p>
                    </div>
                    <x-status-badge :status="$session->status" />
                </div>

                @if ($canUpdate)
                    <details>
                        <summary>Ubah atau hapus sesi</summary>
                        <form method="POST" action="{{ route('admin.sessions.update', $session) }}" class="ev-form">
                            @csrf @method('PUT')
                            <div class="full"><label>Nama sesi</label><input name="name" value="{{ $session->name }}" required class="block w-full"></div>
                            <div><label>Mulai</label><input type="datetime-local" name="start_time" value="{{ $session->start_time->timezone($tz)->format('Y-m-d\TH:i') }}" required class="block w-full"></div>
                            <div><label>Selesai</label><input type="datetime-local" name="end_time" value="{{ $session->end_time->timezone($tz)->format('Y-m-d\TH:i') }}" required class="block w-full"></div>
                            <div>
                                <label>Status</label>
                                <select name="status" class="block w-full">
                                    @foreach (\App\Enums\SessionStatus::cases() as $s)
                                        <option value="{{ $s->value }}" @selected($session->status === $s)>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="full"><button type="submit" class="ev-btn primary sm">Simpan perubahan</button></div>
                        </form>
                        <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}" style="margin-top:.7rem"
                              onsubmit="return confirm('Hapus sesi ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="ev-link danger">Hapus sesi ini</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @empty
        <p class="ev-empty">Belum ada sesi. Tambahkan minimal satu sesi sebelum mempublikasikan acara.</p>
    @endforelse

    @if ($canUpdate)
        <details class="ev-add" @if ($event->sessions->isEmpty()) open @endif>
            <summary>+ Tambah sesi</summary>
            <form method="POST" action="{{ route('admin.sessions.store', $event) }}" class="ev-form">
                @csrf
                <div class="full"><x-field name="name" label="Nama sesi" required /></div>
                <x-field name="start_time" type="datetime-local" label="Mulai" required />
                <x-field name="end_time" type="datetime-local" label="Selesai" required />
                <div class="full"><button type="submit" class="ev-btn primary sm">Tambah sesi</button></div>
            </form>
        </details>
    @endif
</section>

{{-- Penugasan --}}
@if ($canUpdate)
<div class="ev-cols">
    <section class="ev-card" aria-labelledby="tim-admin">
        <div class="ev-ch"><h2 id="tim-admin">Event Admin</h2><p>{{ $admins->count() }} orang</p></div>
        @forelse ($admins as $admin)
            <div class="ev-person">
                <span class="av">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                <div><b>{{ $admin->name }}</b><small>{{ $admin->email }}</small></div>
                @if ($canAssignAdmins)
                    <form method="POST" action="{{ route('admin.members.destroy', [$event, $admin]) }}" onsubmit="return confirm('Cabut penugasan ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="ev-link danger">Cabut</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="ev-empty">Belum ada Event Admin.</p>
        @endforelse
        @if ($canAssignAdmins)
            <form method="POST" action="{{ route('admin.members.store', $event) }}" class="ev-assign">
                @csrf
                <x-select name="user" label="Tugaskan Event Admin" :options="$assignableAdmins->mapWithKeys(fn ($u) => [$u->ulid => $u->name . ' (' . $u->email . ')'])->all()" placeholder="Pilih pengguna" />
                <button type="submit" class="ev-btn sm">Tugaskan</button>
            </form>
        @endif
    </section>

    <section class="ev-card" aria-labelledby="tim-resepsionis">
        <div class="ev-ch"><h2 id="tim-resepsionis">Resepsionis</h2><p>{{ $receptionists->count() }} orang</p></div>
        @forelse ($receptionists as $r)
            <div class="ev-person">
                <span class="av">{{ mb_strtoupper(mb_substr($r->name, 0, 1)) }}</span>
                <div><b>{{ $r->name }}</b><small>{{ $r->email }}</small></div>
                <form method="POST" action="{{ route('admin.members.destroy', [$event, $r]) }}" onsubmit="return confirm('Cabut penugasan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="ev-link danger">Cabut</button>
                </form>
            </div>
        @empty
            <p class="ev-empty">Belum ada resepsionis.</p>
        @endforelse

        @if ($assignableReceptionists->isNotEmpty())
            <form method="POST" action="{{ route('admin.members.store', $event) }}" class="ev-assign">
                @csrf
                <x-select name="user" label="Tugaskan resepsionis yang sudah ada" :options="$assignableReceptionists->mapWithKeys(fn ($u) => [$u->ulid => $u->name . ' (' . $u->email . ')'])->all()" placeholder="Pilih resepsionis" />
                <button type="submit" class="ev-btn sm">Tugaskan</button>
            </form>
        @endif

        <details class="ev-add">
            <summary>+ Buat akun resepsionis baru</summary>
            <form method="POST" action="{{ route('admin.receptionists.store', $event) }}" class="ev-form">
                @csrf
                <x-field name="name" label="Nama" required />
                <x-field name="email" type="email" label="Email" required autocomplete="off" />
                <div class="full"><x-field name="password" type="password" label="Kata sandi" required autocomplete="new-password" hint="Minimal 10 karakter, huruf besar dan kecil, serta angka." /></div>
                <div class="full"><button type="submit" class="ev-btn primary sm">Buat dan tugaskan</button></div>
            </form>
        </details>
    </section>
</div>
@endif

{{-- Zona berbahaya --}}
@if ($canDelete)
<section class="ev-card" aria-labelledby="bahaya" style="border-color:#f0cccc">
    <div class="ev-danger">
        <p id="bahaya"><strong>Arsipkan acara</strong><br>Acara disembunyikan dari daftar. Super Admin masih dapat memulihkannya.</p>
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
              onsubmit="return confirm('Arsipkan acara ini? Acara dapat dipulihkan oleh Super Admin.')">
            @csrf @method('DELETE')
            <button type="submit" class="ev-btn danger">Arsipkan acara</button>
        </form>
    </div>
</section>
@endif
@endsection

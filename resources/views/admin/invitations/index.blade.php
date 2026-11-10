@extends('layouts.app')

@section('title', 'Undangan — ' . $event->name)
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> /
    <a href="{{ route('admin.events.show', $event) }}" class="hover:underline">{{ $event->name }}</a> / Undangan
@endsection

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Undangan <span class="text-base font-normal text-muted">({{ $invitations->total() }})</span></h1>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.events.invitations.store', $event) }}">
                @csrf <input type="hidden" name="all" value="1">
                <button type="submit" class="rounded bg-accent px-4 py-2 text-sm font-medium text-white hover:bg-accent-dark">Buat untuk semua tamu</button>
            </form>
            <form method="POST" action="{{ route('admin.events.invitations.send', $event) }}" onsubmit="return confirm('Kirim email ke semua undangan yang belum terkirim?')">
                @csrf
                <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Kirim email yang belum terkirim</button>
            </form>
        </div>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded border border-line bg-white p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="search" class="block text-sm font-medium">Cari</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama tamu atau kode"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <x-select name="status" label="Status" placeholder="Semua" :value="$filters['status'] ?? ''"
                  :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Terapkan</button>
            <a href="{{ route('admin.events.invitations.index', $event) }}" class="px-2 py-2 text-sm text-muted hover:underline">Atur ulang</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded border border-line bg-white">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="text-left text-muted">
                <tr><th class="px-4 py-3 font-medium">Tamu</th><th class="px-4 py-3 font-medium">Kode</th><th class="px-4 py-3 font-medium">Kuota</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($invitations as $invitation)
                    <tr>
                        <td class="px-4 py-3"><p class="font-medium">{{ $invitation->guest->name }}</p><p class="text-muted">{{ $invitation->guest->email ?? 'Tanpa email' }}</p></td>
                        <td class="px-4 py-3 font-mono">{{ $invitation->invitation_code }}</td>
                        <td class="px-4 py-3">{{ $invitation->quota }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$invitation->status" /></td>
                        <td class="px-4 py-3">
                            @if ($invitation->isRevoked())
                                <span class="text-muted">{{ $invitation->revoke_reason }}</span>
                            @else
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('admin.invitations.preview', $invitation) }}" target="_blank" rel="noopener" class="text-accent hover:underline">Pratinjau</a>
                                    <a href="{{ route('admin.invitations.qr', ['invitation' => $invitation, 'download' => 1]) }}" class="text-accent hover:underline">QR</a>
                                    <form method="POST" action="{{ route('admin.invitations.resend', $invitation) }}">@csrf<button class="hover:underline">Kirim email</button></form>
                                    <form method="POST" action="{{ route('admin.invitations.regenerate', $invitation) }}" onsubmit="return confirm('QR lama akan tidak berlaku. Lanjutkan?')">@csrf<button class="hover:underline">Regenerasi QR</button></form>
                                    <details>
                                        <summary class="cursor-pointer text-danger hover:underline">Cabut</summary>
                                        <form method="POST" action="{{ route('admin.invitations.revoke', $invitation) }}" class="mt-2 flex gap-2">
                                            @csrf
                                            <input name="reason" required maxlength="255" placeholder="Alasan" class="rounded border border-line px-2 py-1 text-sm">
                                            <button class="rounded border border-red-200 px-2 py-1 text-sm text-danger">Cabut</button>
                                        </form>
                                    </details>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-muted">Belum ada undangan. Klik "Buat untuk semua tamu" setelah sesi dan tamu siap.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invitations->links() }}</div>
@endsection

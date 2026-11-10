@extends('layouts.app')

@section('title', 'Pengguna')
@section('breadcrumb', 'Pengguna')

@section('content')
    <div class="page-head">
        <div><h1>Pengguna</h1><p>Kelola akun, peran, dan status akses.</p></div>
        <a href="{{ route('admin.users.create') }}" class="bg-accent" style="padding:.65rem 1.2rem;font-size:.9rem">+ Tambah pengguna</a>
    </div>

    <form method="GET" class="card filter-card">
        <div>
            <label for="search" class="block text-sm font-medium">Cari</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau email"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <x-select name="role" label="Peran" placeholder="Semua" :value="$filters['role'] ?? ''"
                  :options="collect(\App\Enums\UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />
        <div class="btns">
            <button type="submit" class="btn-ghost" style="padding:.65rem 1.1rem;font-size:.88rem">Terapkan</button>
            <a href="{{ route('admin.users.index') }}" class="link-cancel">Atur ulang</a>
        </div>
    </form>

    <div class="tbl-card overflow-x-auto">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="text-left text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Peran</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($users as $u)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="user-cell">
                                <span class="wgs-avatar sm">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                <div><b>{{ $u->name }}</b><small>{{ $u->email }}</small></div>
                            </div>
                        </td>
                        <td class="px-4 py-3"><span class="role-pill {{ $u->role->value }}">{{ $u->role->label() }}</span></td>
                        <td class="px-4 py-3"><x-status-badge :status="$u->status" /></td>
                        <td class="px-4 py-3">
                            @if ($u->isSuperAdmin())
                                <span class="text-muted">Dilindungi</span>
                            @else
                                <div class="act">
                                    <a href="{{ route('admin.users.edit', $u) }}">Ubah</a>
                                    <form method="POST" action="{{ route('admin.users.status', $u) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $u->isActive() ? 'inactive' : 'active' }}">
                                        <button type="submit">{{ $u->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="danger">Hapus</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($users->isEmpty())
            <p class="empty">Tidak ada pengguna yang cocok dengan pencarian.</p>
        @endif
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

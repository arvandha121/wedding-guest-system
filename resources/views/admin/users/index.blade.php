@extends('layouts.app')

@section('title', 'Pengguna')
@section('breadcrumb', 'Pengguna')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Pengguna</h1>
        <a href="{{ route('admin.users.create') }}" class="rounded bg-accent px-4 py-2 text-sm font-medium text-white hover:bg-accent-dark">Tambah pengguna</a>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded border border-line bg-white p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="search" class="block text-sm font-medium">Cari</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau email"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <x-select name="role" label="Peran" placeholder="Semua" :value="$filters['role'] ?? ''"
                  :options="collect(\App\Enums\UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Terapkan</button>
            <a href="{{ route('admin.users.index') }}" class="px-2 py-2 text-sm text-muted hover:underline">Atur ulang</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded border border-line bg-white">
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
                            <p class="font-medium">{{ $u->name }}</p>
                            <p class="text-muted">{{ $u->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $u->role->label() }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$u->status" /></td>
                        <td class="px-4 py-3">
                            @if ($u->isSuperAdmin())
                                <span class="text-muted">Dilindungi</span>
                            @else
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('admin.users.edit', $u) }}" class="text-accent hover:underline">Ubah</a>
                                    <form method="POST" action="{{ route('admin.users.status', $u) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $u->isActive() ? 'inactive' : 'active' }}">
                                        <button type="submit" class="hover:underline">{{ $u->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger hover:underline">Hapus</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

@extends('layouts.app')

@section('title', 'Tamu — ' . $event->name)
@section('breadcrumb')
    <a href="{{ route('admin.events.index') }}" class="hover:underline">Acara</a> /
    <a href="{{ route('admin.events.show', $event) }}" class="hover:underline">{{ $event->name }}</a> / Tamu
@endsection

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Tamu <span class="text-base font-normal text-muted">({{ $guests->total() }})</span></h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.events.guests.export', $event) }}" class="rounded border border-line px-3 py-2 text-sm hover:bg-paper">Export Excel</a>
            <a href="{{ route('admin.events.guests.create', $event) }}" class="rounded bg-accent px-4 py-2 text-sm font-medium text-white hover:bg-accent-dark">Tambah tamu</a>
        </div>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded border border-line bg-white p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="search" class="block text-sm font-medium">Cari</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, keluarga, email, atau telepon"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <x-select name="category" label="Kategori" placeholder="Semua" :value="$filters['category'] ?? ''"
                  :options="$categories->pluck('name', 'ulid')->all()" />
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Terapkan</button>
            <a href="{{ route('admin.events.guests.index', $event) }}" class="px-2 py-2 text-sm text-muted hover:underline">Atur ulang</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded border border-line bg-white">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="text-left text-muted">
                <tr><th class="px-4 py-3 font-medium">Nama</th><th class="px-4 py-3 font-medium">Kategori</th><th class="px-4 py-3 font-medium">Telepon</th><th class="px-4 py-3 font-medium">Kuota</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($guests as $guest)
                    <tr>
                        <td class="px-4 py-3"><p class="font-medium">{{ $guest->name }}</p>@if ($guest->family_name)<p class="text-muted">{{ $guest->family_name }}</p>@endif</td>
                        <td class="px-4 py-3">{{ $guest->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $guest->phone ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $guest->quota }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.guests.edit', $guest) }}" class="text-accent hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('admin.guests.destroy', $guest) }}" onsubmit="return confirm('Hapus tamu ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-muted">Belum ada tamu yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $guests->links() }}</div>

    <section class="mt-8 rounded border border-line bg-white p-5" aria-labelledby="import">
        <h2 id="import" class="text-sm font-medium">Import dari Excel</h2>
        <p class="mt-1 text-sm text-muted">Kolom: nama, nama_keluarga, telepon, email, kategori, kuota, alamat, instansi, catatan. Maksimal 2000 baris.
            <a href="{{ route('admin.events.guests.template', $event) }}" class="text-accent hover:underline">Unduh template</a></p>
        <form method="POST" action="{{ route('admin.events.guests.import', $event) }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="dry_run" value="1"> Pratinjau saja</label>
            <button type="submit" class="rounded border border-line px-4 py-2 text-sm hover:bg-paper">Proses</button>
        </form>
        @error('file') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror

        @if ($report = session('import_report'))
            <div class="mt-4 text-sm">
                <p>Total baris: {{ $report['total'] }} · valid: {{ $report['valid'] }} · dilewati: {{ count($report['skipped']) }} · bermasalah: {{ count($report['errors']) }}</p>
                @if ($report['errors'])
                    <ul class="mt-2 list-disc pl-5 text-danger">
                        @foreach (array_slice($report['errors'], 0, 20) as $e)
                            <li>Baris {{ $e['row'] }}: {{ implode(' ', $e['messages']) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </section>
@endsection

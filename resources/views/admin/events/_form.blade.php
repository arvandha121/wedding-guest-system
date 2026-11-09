@php
    $tz = config('wgs.display_timezone');
    $isEdit = $event->exists;
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}"
      enctype="multipart/form-data" class="space-y-8" novalidate>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <fieldset class="grid gap-4 sm:grid-cols-2">
        <legend class="mb-2 text-sm font-semibold">Identitas acara</legend>
        <x-field name="name" label="Nama acara (internal)" :value="$event->name" required />
        <x-field name="title" label="Judul undangan" :value="$event->title" required />
    </fieldset>

    <fieldset class="grid gap-4 sm:grid-cols-2">
        <legend class="mb-2 text-sm font-semibold">Mempelai</legend>
        <x-field name="groom_name" label="Nama mempelai pria" :value="$event->groom_name" required />
        <x-field name="bride_name" label="Nama mempelai wanita" :value="$event->bride_name" required />
        <x-field name="groom_family" label="Keluarga mempelai pria" :value="$event->groom_family" />
        <x-field name="bride_family" label="Keluarga mempelai wanita" :value="$event->bride_family" />
    </fieldset>

    <fieldset class="grid gap-4 sm:grid-cols-3">
        <legend class="mb-2 text-sm font-semibold">Waktu dan tempat</legend>
        <x-field name="event_date" type="date" label="Tanggal" :value="$event->event_date?->format('Y-m-d')" required />
        <x-field name="start_time" type="time" label="Jam mulai" :value="substr((string) $event->start_time, 0, 5) ?: null" required />
        <x-field name="end_time" type="time" label="Jam selesai" :value="substr((string) $event->end_time, 0, 5) ?: null" required />
        <x-field name="venue_name" label="Nama lokasi" :value="$event->venue_name" required class="sm:col-span-3" />
        <x-textarea name="venue_address" label="Alamat lokasi" :value="$event->venue_address" required class="sm:col-span-3" />
        <x-field name="google_maps_url" type="url" label="Tautan Google Maps" :value="$event->google_maps_url" class="sm:col-span-3"
                 hint="Contoh: https://maps.app.goo.gl/xxxx" />
    </fieldset>

    <fieldset class="grid gap-4 sm:grid-cols-2">
        <legend class="mb-2 text-sm font-semibold">Tampilan undangan</legend>
        <x-select name="theme" label="Tema" :options="collect($themes)->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all()" :value="$event->theme ?? 'classic'" />
        <div></div>
        <div>
            <label for="banner" class="block text-sm font-medium">Banner</label>
            <input id="banner" name="banner" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
            <p class="mt-1 text-xs text-muted">JPG, PNG, atau WebP, maksimal 5 MB. Disimpan sebagai WebP.</p>
            @error('banner') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
            @if ($isEdit && $event->banner_path)
                <img src="{{ $event->bannerUrl() }}" alt="Banner saat ini" class="mt-2 h-24 rounded border border-line object-cover">
                <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_banner" value="1"> Hapus banner</label>
            @endif
        </div>
        <div>
            <label for="couple_photo" class="block text-sm font-medium">Foto mempelai</label>
            <input id="couple_photo" name="couple_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
            @error('couple_photo') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
            @if ($isEdit && $event->couple_photo_path)
                <img src="{{ $event->couplePhotoUrl() }}" alt="Foto mempelai saat ini" class="mt-2 h-24 rounded border border-line object-cover">
                <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_couple_photo" value="1"> Hapus foto</label>
            @endif
        </div>
        <x-textarea name="description" label="Deskripsi" :value="$event->description" rows="4" class="sm:col-span-2" />
    </fieldset>

    <fieldset class="grid gap-4 sm:grid-cols-2">
        <legend class="mb-2 text-sm font-semibold">Check-in</legend>
        <x-select name="checkin_mode" label="Mode check-in"
                  :options="collect($checkinModes)->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all()"
                  :value="$event->checkin_mode?->value ?? 'single'" />
        <div class="flex items-end">
            <input type="hidden" name="allow_manual_checkin" value="0">
            <label class="flex items-center gap-2 pb-2 text-sm">
                <input type="checkbox" name="allow_manual_checkin" value="1" @checked(old('allow_manual_checkin', $event->allow_manual_checkin ?? true))>
                Izinkan check-in manual
            </label>
        </div>
        <x-field name="checkin_opens_at" type="datetime-local" label="Check-in dibuka"
                 :value="$event->checkin_opens_at?->timezone($tz)->format('Y-m-d\TH:i')" />
        <x-field name="checkin_closes_at" type="datetime-local" label="Check-in ditutup"
                 :value="$event->checkin_closes_at?->timezone($tz)->format('Y-m-d\TH:i')" />
    </fieldset>

    <fieldset class="grid gap-4 sm:grid-cols-2">
        <legend class="mb-2 text-sm font-semibold">Kontak penyelenggara</legend>
        <x-field name="contact_name" label="Nama kontak" :value="$event->contact_name" />
        <x-field name="contact_phone" type="tel" label="Nomor telepon" :value="$event->contact_phone" />
    </fieldset>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded bg-accent px-5 py-2.5 text-sm font-medium text-white hover:bg-accent-dark">
            {{ $isEdit ? 'Simpan perubahan' : 'Buat acara' }}
        </button>
        <a href="{{ $isEdit ? route('admin.events.show', $event) : route('admin.events.index') }}" class="text-sm text-muted hover:underline">Batal</a>
    </div>
</form>

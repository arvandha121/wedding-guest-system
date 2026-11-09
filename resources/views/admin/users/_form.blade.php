@php $isEdit = isset($user) && $user->exists; @endphp

<form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-lg space-y-5" novalidate>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-field name="name" label="Nama" :value="$isEdit ? $user->name : null" required />
    <x-field name="email" type="email" label="Email" :value="$isEdit ? $user->email : null" required autocomplete="off" />
    <x-field name="password" type="password" :label="$isEdit ? 'Kata sandi baru' : 'Kata sandi'" :required="! $isEdit" autocomplete="new-password"
             :hint="$isEdit ? 'Kosongkan bila tidak ingin mengubah.' : 'Minimal 10 karakter, huruf besar dan kecil, serta angka.'" />

    @if ($isEdit)
        <div>
            <p class="text-sm font-medium">Peran</p>
            <p class="mt-1 text-sm">{{ $user->role->label() }} <span class="text-muted">(tidak dapat diubah)</span></p>
        </div>
    @else
        <x-select name="role" label="Peran" required placeholder="Pilih peran"
                  :options="[\App\Enums\UserRole::EventAdmin->value => 'Event Admin', \App\Enums\UserRole::Receptionist->value => 'Resepsionis']" />
    @endif

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded bg-accent px-5 py-2.5 text-sm font-medium text-white hover:bg-accent-dark">{{ $isEdit ? 'Simpan perubahan' : 'Buat pengguna' }}</button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-muted hover:underline">Batal</a>
    </div>
</form>

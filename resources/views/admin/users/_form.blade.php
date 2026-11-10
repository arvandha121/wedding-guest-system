@php
    $isEdit = isset($user) && $user->exists;
    $roles = [
        \App\Enums\UserRole::EventAdmin->value => ['Event Admin', 'Mengelola acara, tamu, dan undangan.'],
        \App\Enums\UserRole::Receptionist->value => ['Resepsionis', 'Melakukan check-in tamu di lokasi acara.'],
    ];
    $currentRole = old('role', $isEdit ? $user->role->value : null);
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" class="form-card" novalidate>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <h2>Informasi akun</h2>
    <x-field name="name" label="Nama" :value="$isEdit ? $user->name : null" required />
    <x-field name="email" type="email" label="Email" :value="$isEdit ? $user->email : null" required autocomplete="off" />
    <x-field name="password" type="password" :label="$isEdit ? 'Kata sandi baru' : 'Kata sandi'" :required="! $isEdit" autocomplete="new-password"
             :hint="$isEdit ? 'Kosongkan bila tidak ingin mengubah.' : 'Minimal 10 karakter, huruf besar dan kecil, serta angka.'" />

    <hr>
    <h2>Peran & akses</h2>
    <div class="role-grid">
        @foreach ($roles as $value => [$label, $desc])
            <label class="role-opt">
                <input type="radio" name="role" value="{{ $value }}" @checked($currentRole === $value) required>
                <b>{{ $label }}</b>
                <span>{{ $desc }}</span>
            </label>
        @endforeach
    </div>
    @error('role')
        <p class="text-sm text-danger" role="alert">{{ $message }}</p>
    @enderror
    @if ($isEdit)
        <p class="notice">Mengganti peran akan mengeluarkan pengguna dari semua acara yang ditugaskan dan memaksanya masuk ulang. Penugasan acara perlu dibuat kembali.</p>
    @endif

    <div class="form-actions">
        <button type="submit" class="bg-accent">{{ $isEdit ? 'Simpan perubahan' : 'Buat pengguna' }}</button>
        <a href="{{ route('admin.users.index') }}" class="link-cancel">Batal</a>
    </div>
</form>

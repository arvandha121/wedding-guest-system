@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h1 class="mt-2 text-2xl font-semibold">Masuk</h1>
    <p class="mt-1 text-sm text-muted">Gunakan akun yang diberikan oleh penyelenggara acara.</p>

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" novalidate>
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2.5 text-base focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
            @error('email')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded border border-line bg-white px-3 py-2.5 text-base focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
            @error('password')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" class="rounded border-line text-accent focus:ring-accent">
            Ingat saya di perangkat ini
        </label>

        <button type="submit"
                class="w-full rounded bg-accent px-4 py-2.5 text-base font-medium text-white hover:bg-accent-dark focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
            Masuk
        </button>
    </form>
@endsection

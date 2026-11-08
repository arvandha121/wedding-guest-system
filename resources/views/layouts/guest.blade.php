<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <main class="mx-auto flex min-h-screen max-w-sm flex-col justify-center px-6 py-12">
        <p class="text-sm tracking-wide text-muted">{{ config('app.name') }}</p>
        @yield('content')
    </main>
</body>
</html>

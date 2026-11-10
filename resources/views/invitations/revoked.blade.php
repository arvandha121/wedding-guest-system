<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Undangan tidak berlaku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@400;500&display=swap">
    <link rel="stylesheet" href="{{ asset('css/wgs/invitation.css') }}">
</head>
<body class="inv" data-theme="{{ $event->theme ?? 'classic' }}">
<main class="inv-center">
<div class="inv-card">
    <p class="inv-k">Undangan</p>
    <h1 class="inv-guest">Undangan ini tidak lagi berlaku</h1>
    <p class="inv-sub">Silakan hubungi penyelenggara acara bila Anda merasa ini keliru.</p>
    @if ($event->contact_name || $event->contact_phone)
        <span class="inv-pill">{{ trim(($event->contact_name ?? '') . ' ' . ($event->contact_phone ?? '')) }}</span>
    @endif
</div>
</main>
</body>
</html>

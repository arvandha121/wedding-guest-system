@props(['href', 'active' => false, 'icon' => 'home'])

@php
    $paths = [
        'home' => '<path d="M3 11l9-8 9 8M5 10v10h14V10"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5M16 4.8a3.5 3.5 0 010 6.4M18 14.8c1.8.6 3.1 2.2 3.5 5.2"/>',
    ];
@endphp

<a href="{{ $href }}" class="nav-item" @if ($active) aria-current="page" @endif>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$icon] ?? $paths['home'] !!}</svg>
    {{ $slot }}
</a>

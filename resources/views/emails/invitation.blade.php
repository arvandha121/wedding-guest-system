@php
    $e = $invitation->event;
    $tz = config('wgs.display_timezone');
    $themes = [
        'classic' => ['bg' => '#f4eee2', 'hero' => '#5b4223', 'hero2' => '#7a5c2e', 'ac' => '#9a7332', 'gold' => '#e3c585'],
        'minimal' => ['bg' => '#efefed', 'hero' => '#111111', 'hero2' => '#2e2e2c', 'ac' => '#111111', 'gold' => '#d9d9d4'],
        'garden'  => ['bg' => '#e6f0e3', 'hero' => '#1f4a37', 'hero2' => '#37795a', 'ac' => '#2f6b4f', 'gold' => '#d8e9b8'],
    ];
    $c = $themes[$e->theme ?? 'classic'] ?? $themes['classic'];
    $serif = "'Cormorant Garamond',Georgia,'Times New Roman',serif";
    $sans = "'Helvetica Neue',Arial,sans-serif";
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $e->title }}</title>
    <style>
        @media only screen and (max-width:600px){
            .wrap{width:100%!important}
            .px{padding-left:22px!important;padding-right:22px!important}
            .names{font-size:34px!important}
            .btn a{display:block!important}
        }
    </style>
</head>
<body style="margin:0;padding:0;background:{{ $c['bg'] }};">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Anda diundang ke pernikahan {{ $e->groom_name }} &amp; {{ $e->bride_name }}.</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{{ $c['bg'] }}" style="background:{{ $c['bg'] }};">
<tr><td align="center" style="padding:28px 12px;">

<table role="presentation" class="wrap" width="560" cellpadding="0" cellspacing="0" border="0" style="width:560px;max-width:100%;background:#fffdf8;border-radius:24px;overflow:hidden;">

    {{-- Hero --}}
    <tr><td class="px" align="center" bgcolor="{{ $c['hero'] }}" style="background-color:{{ $c['hero'] }};background-image:linear-gradient(160deg,{{ $c['hero'] }},{{ $c['hero2'] }});padding:44px 40px 40px;color:#ffffff;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            <td align="center" width="64" height="64" style="width:64px;height:64px;border:1px solid {{ $c['gold'] }};border-radius:32px;color:{{ $c['gold'] }};font-family:{{ $serif }};font-size:22px;font-style:italic;">
                {{ mb_strtoupper(mb_substr($e->groom_name, 0, 1)) }}&amp;{{ mb_strtoupper(mb_substr($e->bride_name, 0, 1)) }}
            </td>
        </tr></table>
        <p style="margin:22px 0 0;font-family:{{ $sans }};font-size:12px;letter-spacing:4px;text-transform:uppercase;color:{{ $c['gold'] }};">Undangan Pernikahan</p>
        <h1 class="names" style="margin:14px 0 0;font-family:{{ $serif }};font-size:42px;line-height:1.1;font-weight:normal;color:#ffffff;">
            {{ $e->groom_name }}<br><span style="font-size:26px;font-style:italic;color:{{ $c['gold'] }};">&amp;</span><br>{{ $e->bride_name }}
        </h1>
        <p style="margin:20px 0 0;font-family:{{ $sans }};font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#ffffff;">{{ $e->event_date->locale('id')->translatedFormat('l, d F Y') }}</p>
    </td></tr>

    {{-- Sapaan --}}
    <tr><td class="px" align="center" style="padding:38px 40px 8px;font-family:{{ $serif }};color:#2b2118;">
        <p style="margin:0;font-size:16px;font-style:italic;color:#7a6f62;">Kepada Yth.</p>
        <p style="margin:6px 0 0;font-size:30px;line-height:1.2;">{{ $invitation->guest->name }}</p>
        @if ($invitation->guest->family_name)
            <p style="margin:4px 0 0;font-size:16px;color:#7a6f62;">{{ $invitation->guest->family_name }}</p>
        @endif
        <p style="margin:18px 0 0;font-family:{{ $sans }};font-size:15px;line-height:1.7;color:#4a4036;">Dengan penuh sukacita, kami mengundang Anda untuk hadir dan memberikan doa restu pada acara pernikahan kami.</p>
        <p style="margin:12px 0 0;"><span style="display:inline-block;padding:5px 14px;border-radius:999px;background:#f1eadb;color:{{ $c['ac'] }};font-family:{{ $sans }};font-size:12px;font-weight:bold;">Berlaku untuk {{ $invitation->quota }} orang</span></p>
    </td></tr>

    {{-- Tombol --}}
    <tr><td class="px btn" align="center" style="padding:26px 40px 8px;">
        <a href="{{ $url }}" style="display:inline-block;padding:15px 38px;background:{{ $c['ac'] }};color:#ffffff;border-radius:999px;font-family:{{ $sans }};font-size:15px;font-weight:bold;text-decoration:none;">Buka Undangan Digital</a>
    </td></tr>

    {{-- Acara --}}
    <tr><td class="px" style="padding:34px 40px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e6dcc8;"><tr><td align="center" style="padding-top:26px;">
            <p style="margin:0 0 14px;font-family:{{ $sans }};font-size:11px;letter-spacing:4px;text-transform:uppercase;color:{{ $c['ac'] }};">Acara</p>
            @foreach ($invitation->sessions as $s)
                <p style="margin:{{ $loop->first ? '0' : '14px' }} 0 0;font-family:{{ $serif }};font-size:22px;color:#2b2118;">{{ $s->name }}</p>
                <p style="margin:2px 0 0;font-family:{{ $sans }};font-size:14px;color:#7a6f62;">{{ $s->start_time->timezone($tz)->locale('id')->translatedFormat('d F Y, H:i') }} – {{ $s->end_time->timezone($tz)->format('H:i') }} WIB</p>
            @endforeach
        </td></tr></table>
    </td></tr>

    {{-- Lokasi --}}
    <tr><td class="px" style="padding:26px 40px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e6dcc8;"><tr><td align="center" style="padding-top:26px;">
            <p style="margin:0 0 14px;font-family:{{ $sans }};font-size:11px;letter-spacing:4px;text-transform:uppercase;color:{{ $c['ac'] }};">Lokasi</p>
            <p style="margin:0;font-family:{{ $serif }};font-size:22px;color:#2b2118;">{{ $e->venue_name }}</p>
            <p style="margin:2px 0 0;font-family:{{ $sans }};font-size:14px;line-height:1.6;color:#7a6f62;">{{ $e->venue_address }}</p>
            @if ($e->google_maps_url)
                <p style="margin:14px 0 0;"><a href="{{ $e->google_maps_url }}" style="display:inline-block;padding:9px 22px;border:1px solid {{ $c['ac'] }};border-radius:999px;color:{{ $c['ac'] }};font-family:{{ $sans }};font-size:13px;font-weight:bold;text-decoration:none;">Buka Google Maps</a></p>
            @endif
        </td></tr></table>
    </td></tr>

    {{-- Catatan + kontak --}}
    <tr><td class="px" align="center" style="padding:30px 40px 38px;">
        <p style="margin:0;padding:14px 16px;background:#f7f2e6;border-radius:14px;font-family:{{ $sans }};font-size:13px;line-height:1.6;color:#6b5f50;">Tunjukkan <strong>QR Code</strong> pada undangan digital kepada penerima tamu saat tiba. Mohon tidak membagikan tautan ini kepada orang lain.</p>
        @if ($e->contact_name || $e->contact_phone)
            <p style="margin:18px 0 0;font-family:{{ $sans }};font-size:12px;color:#7a6f62;">Kontak: {{ trim(($e->contact_name ?? '') . ' ' . ($e->contact_phone ?? '')) }}</p>
        @endif
    </td></tr>

</table>

<p style="margin:18px 0 0;font-family:{{ $sans }};font-size:11px;color:#8a8070;text-align:center;">Tombol tidak berfungsi? Salin tautan ini:<br><a href="{{ $url }}" style="color:{{ $c['ac'] }};word-break:break-all;">{{ $url }}</a></p>

</td></tr>
</table>
</body>
</html>

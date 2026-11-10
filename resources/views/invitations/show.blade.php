<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $event->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@400;500&display=swap">
    <link rel="stylesheet" href="{{ asset('css/wgs/invitation.css') }}">
</head>
<body class="inv" data-theme="{{ $event->theme ?? 'classic' }}">
@php $tz = config('wgs.display_timezone'); @endphp
@if ($preview)
    <div class="inv-preview">Pratinjau admin — halaman ini tidak menandai undangan sebagai dibuka.</div>
@endif
<main class="inv-wrap">
<div class="inv-card inv-deck" id="deck">
    <div class="inv-track" id="track">
        <section class="inv-slide inv-cover on" @if ($event->bannerUrl()) style="background-image:linear-gradient(rgba(0,0,0,.45),rgba(0,0,0,.65)),url('{{ $event->bannerUrl() }}')" @endif>
            <div class="inv-in" style="text-align:center">
                <div class="inv-mono">{{ mb_strtoupper(mb_substr($event->groom_name, 0, 1)) }} &amp; {{ mb_strtoupper(mb_substr($event->bride_name, 0, 1)) }}</div>
                <p class="inv-k">Undangan Pernikahan</p>
                <h1 class="inv-names">{{ $event->groom_name }}<span class="inv-amp">&amp;</span>{{ $event->bride_name }}</h1>
                <p class="inv-date">{{ $event->event_date->locale('id')->translatedFormat('l, d F Y') }}</p>
                @php $start = $invitation->sessions->sortBy('start_time')->first()?->start_time; @endphp
                @if ($start && $start->isFuture())
                    <div class="inv-cd" data-cd="{{ $start->toIso8601String() }}">
                        @foreach (['d' => 'Hari', 'h' => 'Jam', 'm' => 'Menit', 's' => 'Detik'] as $k => $l)
                            <div><b data-u="{{ $k }}">00</b><small>{{ $l }}</small></div>
                        @endforeach
                    </div>
                @endif
                <p class="inv-hint">Geser untuk membuka <span>&rarr;</span></p>
            </div>
        </section>

        <section class="inv-slide" style="text-align:center">
            <div class="inv-in">
                <p class="inv-to">Kepada Yth.</p>
                <p class="inv-guest">{{ $invitation->guest->name }}</p>
                @if ($invitation->guest->family_name)<p class="inv-sub">{{ $invitation->guest->family_name }}</p>@endif
                <span class="inv-pill">Berlaku untuk {{ $invitation->quota }} orang</span>
            </div>
        </section>

        <section class="inv-slide" style="text-align:center">
            <div class="inv-in">
                <h2 class="inv-k">Acara</h2>
                <ul class="inv-list">
                    @foreach ($invitation->sessions as $session)
                        <li>
                            <p class="inv-t">{{ $session->name }}</p>
                            <p class="inv-m">{{ $session->start_time->timezone($tz)->locale('id')->translatedFormat('d F Y, H:i') }} – {{ $session->end_time->timezone($tz)->format('H:i') }} WIB</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="inv-slide" style="text-align:center">
            <div class="inv-in">
                <h2 class="inv-k">Lokasi</h2>
                <p class="inv-t">{{ $event->venue_name }}</p>
                <p class="inv-m">{{ $event->venue_address }}</p>
                @if ($event->google_maps_url)
                    <a href="{{ $event->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="inv-btn">Buka Google Maps</a>
                @endif
            </div>
        </section>

        <section class="inv-slide" style="text-align:center">
            <div class="inv-in">
                <h2 class="inv-k">Tiket Masuk</h2>
                <div class="inv-ticket">
                    <p class="inv-t">{{ $invitation->guest->name }}</p>
                    <p class="inv-m">{{ $invitation->quota }} orang</p>
                    <hr>
                    <img src="{{ $qrUrl }}" alt="QR Code undangan atas nama {{ $invitation->guest->name }}">
                    <span class="inv-code">{{ $invitation->invitation_code }}</span>
                </div>
                <p class="inv-note">Tunjukkan QR ini kepada penerima tamu. Jangan dibagikan ke orang lain.</p>
                <a href="{{ $downloadUrl }}" class="inv-btn fill">Unduh QR Code</a>
                @if ($event->contact_name || $event->contact_phone)
                    <p class="inv-foot">Kontak: {{ trim(($event->contact_name ?? '') . ' ' . ($event->contact_phone ?? '')) }}</p>
                @endif
            </div>
        </section>
    </div>

    <button type="button" class="inv-nav prev" data-go="-1" aria-label="Sebelumnya">&lsaquo;</button>
    <button type="button" class="inv-nav next" data-go="1" aria-label="Berikutnya">&rsaquo;</button>
    <nav class="inv-dots" aria-label="Halaman undangan">
        @foreach (['Sampul', 'Tamu', 'Acara', 'Lokasi', 'Tiket'] as $i => $n)
            <button type="button" data-i="{{ $i }}" aria-label="{{ $n }}"></button>
        @endforeach
    </nav>
</div>
</main>
<script>
const deck=document.getElementById('deck'),tr=document.getElementById('track'),sl=[...tr.children],dots=[...deck.querySelectorAll('.inv-dots button')],desk=matchMedia('(min-width:1024px)');
let cur=0,lock=0;
const W=()=>desk.matches?tr.clientWidth*.58:tr.clientWidth,off=()=>desk.matches?1:0;
const go=(i,b='smooth')=>tr.scrollTo({left:(Math.max(off(),Math.min(sl.length-1,i))-off())*W(),behavior:b});
const upd=()=>{cur=Math.round(tr.scrollLeft/W())+off();
dots.forEach((d,i)=>d.classList.toggle('on',i===cur));sl.forEach((s,i)=>s.classList.toggle('on',i===cur));
deck.classList.toggle('on-cover',cur===0);deck.dataset.i=cur;deck.dataset.last=cur===sl.length-1?1:0};
tr.addEventListener('scroll',upd,{passive:true});
dots.forEach((d,i)=>d.onclick=()=>go(i));
deck.querySelectorAll('[data-go]').forEach(b=>b.onclick=()=>go(cur+ +b.dataset.go));
addEventListener('keydown',e=>{if(e.key==='ArrowRight')go(cur+1);if(e.key==='ArrowLeft')go(cur-1)});
addEventListener('resize',()=>{go(cur,'auto');upd()});
deck.addEventListener('wheel',e=>{const s=sl[cur];if(!desk.matches||Math.abs(e.deltaY)<20||Date.now()<lock||s.scrollHeight>s.clientHeight+2)return;lock=Date.now()+700;go(cur+(e.deltaY>0?1:-1))},{passive:true});
upd();
const t=document.querySelector('[data-cd]');
if(t){const d=new Date(t.dataset.cd).getTime(),u=t.querySelectorAll('[data-u]'),f=()=>{const s=Math.max(0,Math.floor((d-Date.now())/1e3));
[s/86400,s%86400/3600,s%3600/60,s%60].forEach((v,i)=>u[i].textContent=String(Math.floor(v)).padStart(2,'0'))};f();setInterval(f,1e3)}
</script>
</body>
</html>

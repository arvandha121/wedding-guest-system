@props(['href', 'active' => false])

<a href="{{ $href }}"
   @class([
       'block rounded px-3 py-2 text-sm',
       'bg-accent text-white font-medium' => $active,
       'text-ink hover:bg-paper' => ! $active,
   ])
   @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>

@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = is_object($status) && method_exists($status, 'label') ? $status->label() : ucfirst($value);
    $tone = match ($value) {
        'published', 'active', 'open' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'ongoing' => 'border-amber-200 bg-amber-50 text-amber-800',
        'completed', 'closed' => 'border-stone-200 bg-stone-100 text-stone-700',
        'cancelled', 'inactive' => 'border-red-200 bg-red-50 text-red-800',
        default => 'border-line bg-white text-ink',
    };
@endphp

<span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ $label }}</span>

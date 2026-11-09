@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'autocomplete' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium">
        {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
    </label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @required($required)
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
    @if ($hint)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
    @enderror
</div>

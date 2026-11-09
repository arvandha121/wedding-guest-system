@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 3])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium">
        {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
    </label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              class="mt-1 block w-full rounded border border-line bg-white px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
    @enderror
</div>

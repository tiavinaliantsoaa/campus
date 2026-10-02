@props(['name', 'label', 'type' => 'text', 'value' => null])
<label class="block space-y-1.5">
    <span class="text-sm font-medium text-ink">{{ $label }}</span>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $type === 'password' ? '' : $value }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-campus focus:ring-2 focus:ring-campus/15']) }}
    >
    @error($name)
        <span class="block text-sm text-campus">{{ $message }}</span>
    @enderror
</label>

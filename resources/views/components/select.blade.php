@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => 'Sélectionner'])
<label class="block space-y-1.5">
    <span class="text-sm font-medium text-ink">{{ $label }}</span>
    <select name="{{ $name }}" id="{{ $name }}" {{ $attributes->merge(['class' => 'w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-campus focus:ring-2 focus:ring-campus/15']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <span class="block text-sm text-campus">{{ $message }}</span>
    @enderror
</label>

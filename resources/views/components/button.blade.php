@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])
@php
    $styles = [
        'primary' => 'bg-campus text-white hover:bg-campus-dark shadow-sm',
        'secondary' => 'border border-line bg-white text-ink hover:bg-canvas',
        'ghost' => 'text-ink/80 hover:bg-black/5',
        'danger' => 'border border-campus/20 bg-white text-campus hover:bg-campus/5',
    ][$variant];
    $class = "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition {$styles}";
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif

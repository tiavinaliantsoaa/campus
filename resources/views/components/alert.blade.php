@props(['tone' => 'info'])
@php
    $styles = [
        'info' => 'border-sky-100 bg-sky-50 text-sky-900',
        'warning' => 'border-amber-100 bg-amber-50 text-amber-950',
        'danger' => 'border-red-100 bg-red-50 text-red-900',
        'success' => 'border-emerald-100 bg-emerald-50 text-emerald-900',
    ][$tone];
@endphp
<div {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm {$styles}"]) }}>{{ $slot }}</div>

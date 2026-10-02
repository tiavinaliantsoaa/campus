@props(['tone' => 'neutral'])
@php
    $styles = [
        'neutral' => 'bg-black/5 text-ink/70',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-campus/10 text-campus',
        'info' => 'bg-sky-50 text-sky-800',
    ][$tone] ?? 'bg-black/5 text-ink/70';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {$styles}"]) }}>{{ $slot }}</span>

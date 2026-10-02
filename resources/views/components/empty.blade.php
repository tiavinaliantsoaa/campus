@props(['title', 'description' => null])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-line bg-white px-6 py-14 text-center']) }}>
    <p class="text-base font-medium text-ink">{{ $title }}</p>
    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-ink/60">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>

@props(['eyebrow' => null, 'title', 'subtitle' => null])
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if ($eyebrow)
            <p class="text-xs font-semibold tracking-[0.16em] text-campus uppercase">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-2 text-sm text-ink/55">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>

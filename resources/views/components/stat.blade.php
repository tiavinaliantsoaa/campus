@props(['label', 'value', 'hint' => null, 'icon' => 'chart'])
<x-card class="p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm text-ink/55">{{ $label }}</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-ink">{{ $value }}</p>
            @if ($hint)
                <p class="mt-2 text-xs text-ink/45">{{ $hint }}</p>
            @endif
        </div>
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-campus/10 text-campus">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</x-card>

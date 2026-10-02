<div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-ink/40 lg:hidden"></div>
<aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-[260px] -translate-x-full flex-col bg-sidebar text-white transition lg:static lg:translate-x-0">
    <div class="px-6 pt-7">
        <a href="{{ route('dashboard') }}" class="block">
            <span class="text-2xl font-semibold tracking-tight">{{ config('campus.short_name') }}</span>
            <span class="mt-1 block text-[11px] tracking-[0.18em] text-white/45 uppercase">Business School</span>
        </a>
    </div>

    <div class="mx-4 mt-6 flex items-center justify-between rounded-xl px-3 py-2 text-[11px] tracking-[0.14em] text-white/45 uppercase">
        <span>Campus</span>
        <span>{{ $currentYear?->name ?? '—' }}</span>
    </div>

    <nav class="mt-2 flex-1 space-y-1 overflow-y-auto px-3 pb-4">
        @foreach ($navItems as $item)
            @php
                $active = collect(explode('|', $item['active']))->contains(fn (string $pattern): bool => request()->routeIs($pattern));
            @endphp
            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ $active ? 'bg-campus text-white' : 'text-white/75 hover:bg-white/5 hover:text-white' }}">
                <x-icon :name="$item['icon']" class="h-[18px] w-[18px]" />
                <span class="flex-1">{{ $item['label'] }}</span>
                @if ($item['badge'] === 'attendance' && ($attendanceBadge ?? 0) > 0)
                    <span class="rounded-full bg-white/15 px-2 py-0.5 text-xs">{{ $attendanceBadge }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 px-5 py-4 text-xs text-white/45">
        <p>{{ config('campus.tagline') }}</p>
        <p class="mt-1">{{ config('campus.city') }} · {{ config('campus.country') }}</p>
    </div>

    <div class="mx-3 mb-4 flex items-center gap-3 rounded-2xl bg-white/5 px-3 py-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-campus text-sm font-semibold">{{ auth()->user()->initials() }}</span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-medium">{{ auth()->user()->name }}</span>
            <span class="block truncate text-xs text-white/50">{{ auth()->user()->roleLabel() }}</span>
        </span>
    </div>
</aside>

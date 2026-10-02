<header class="sticky top-0 z-20 border-b border-line/80 bg-canvas/90 backdrop-blur">
    <div class="flex items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <button type="button" data-sidebar-toggle class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-line bg-white lg:hidden" aria-label="Ouvrir le menu">
            <x-icon name="groups" class="h-5 w-5" />
        </button>

        <p class="hidden min-w-0 text-sm text-ink/50 sm:block">
            <span>{{ config('campus.short_name') }} Campus</span>
            <span class="px-1.5">/</span>
            <span class="text-ink">@yield('breadcrumb', 'Vue d\'ensemble')</span>
        </p>

        <form action="{{ route('search') }}" method="GET" class="ml-auto hidden w-full max-w-xs md:block">
            <label class="relative block">
                <span class="sr-only">Rechercher</span>
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-ink/40" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher" class="w-full rounded-lg border border-line bg-white py-2 pr-3 pl-9 text-sm outline-none focus:border-campus">
            </label>
        </form>

        <div class="ml-auto flex items-center gap-2 md:ml-0">
            <div data-dropdown class="relative">
                <button type="button" data-dropdown-toggle class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-line bg-white" aria-label="Notifications">
                    <x-icon name="bell" class="h-4 w-4" />
                </button>
                <div data-dropdown-panel class="absolute right-0 z-30 mt-2 hidden w-80 rounded-2xl border border-line bg-white p-2 shadow-lg">
                    @forelse (auth()->user()->notifications()->latest()->limit(6)->get() as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-canvas {{ $notification->read_at ? 'text-ink/50' : 'text-ink' }}">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </button>
                        </form>
                    @empty
                        <p class="px-3 py-4 text-sm text-ink/50">Aucune notification.</p>
                    @endforelse
                </div>
            </div>

            @if (($availableYears ?? collect())->isNotEmpty())
                <form method="POST" action="{{ route('academic-years.select') }}">
                    @csrf
                    <label class="sr-only" for="academic_year_id">Année académique</label>
                    <select id="academic_year_id" name="academic_year_id" onchange="this.form.submit()" class="max-w-[11rem] rounded-lg border border-line bg-white px-3 py-2 text-sm">
                        @foreach ($availableYears as $yearOption)
                            <option value="{{ $yearOption->id }}" @selected($currentYear?->id === $yearOption->id)>{{ $yearOption->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div data-dropdown class="relative">
                <button type="button" data-dropdown-toggle class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-2 py-1.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white">{{ auth()->user()->initials() }}</span>
                    <span class="hidden text-sm sm:block">{{ auth()->user()->name }}</span>
                </button>
                <div data-dropdown-panel class="absolute right-0 z-30 mt-2 hidden w-52 rounded-2xl border border-line bg-white p-2 shadow-lg">
                    <a href="{{ route('account.edit') }}" class="block rounded-xl px-3 py-2 text-sm hover:bg-canvas">Mon compte</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-canvas">Déconnexion</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

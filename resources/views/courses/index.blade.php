@extends('layouts.app')
@section('title', 'Emploi du temps')
@section('breadcrumb', 'Emploi du temps')
@section('content')
    <x-page-header title="Emploi du temps" :subtitle="$start->translatedFormat('j F').' — '.$end->translatedFormat('j F Y')">
        @can('create', App\Models\Course::class)
            <x-button :href="route('courses.create')">Planifier un cours</x-button>
        @endcan
    </x-page-header>

    <x-card class="space-y-4 p-4">
        <form method="GET" data-auto-submit class="grid gap-3 md:grid-cols-5">
            <input type="date" name="date" value="{{ $anchor->toDateString() }}" class="rounded-lg border border-line px-3 py-2 text-sm">
            <select name="view" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="week" @selected($view === 'week')>Semaine</option>
                <option value="day" @selected($view === 'day')>Jour</option>
            </select>
            <select name="group" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Groupe</option>
                @foreach ($groups as $group)<option value="{{ $group->id }}" @selected((string) request('group') === (string) $group->id)>{{ $group->name }}</option>@endforeach
            </select>
            <select name="teacher" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Enseignant</option>
                @foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) request('teacher') === (string) $teacher->id)>{{ $teacher->full_name }}</option>@endforeach
            </select>
            <select name="room" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Salle</option>
                @foreach ($rooms as $room)<option value="{{ $room->id }}" @selected((string) request('room') === (string) $room->id)>{{ $room->code }}</option>@endforeach
            </select>
        </form>

        @if ($groups->isNotEmpty())
            <div class="flex flex-wrap gap-2 border-t border-line pt-4">
                @foreach ($groups as $group)
                    <div class="flex items-center gap-2 rounded-full border border-line bg-canvas px-2 py-1.5">
                        @if (auth()->user()->hasPermission('groups.manage'))
                            <form method="POST" action="{{ route('groups.color', $group) }}" data-auto-submit>
                                @csrf
                                @method('PATCH')
                                <label class="sr-only" for="color-{{ $group->id }}">Couleur de {{ $group->name }}</label>
                                <input id="color-{{ $group->id }}" type="color" name="color" value="{{ $group->calendarColor() }}" class="h-6 w-6 cursor-pointer rounded-full border-0 bg-transparent p-0">
                            </form>
                        @else
                            <span class="h-3.5 w-3.5 rounded-full" style="background-color: {{ $group->calendarColor() }}"></span>
                        @endif
                        <span class="text-xs font-medium text-ink">{{ $group->name }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    @if ($view === 'week')
        <div class="hidden gap-3 lg:grid lg:grid-cols-5">
            @foreach (range(0, 4) as $offset)
                @php $day = $start->copy()->addDays($offset); $dayCourses = $courses->filter(fn ($course) => $course->starts_at->isSameDay($day)); @endphp
                <x-card class="min-h-80 p-3">
                    <p class="text-sm font-medium">{{ $day->translatedFormat('l j') }}</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($dayCourses as $course)
                            <article class="rounded-xl border border-l-4 p-3 text-xs {{ $course->status->value === 'cancelled' ? 'opacity-50' : '' }}" style="border-color: {{ $course->group->calendarColor() }}; background-color: color-mix(in srgb, {{ $course->group->calendarColor() }} 10%, white);">
                                <p class="font-medium">{{ $course->starts_at->format('H:i') }}–{{ $course->ends_at->format('H:i') }}</p>
                                <p>{{ $course->displayTitle() }}</p>
                                <p class="text-ink/55">{{ $course->group->code }} · {{ $course->room?->code ?? '—' }}</p>
                                @can('update', $course)
                                    <a href="{{ route('courses.edit', $course) }}" class="mt-1 inline-block text-campus">Modifier</a>
                                @endcan
                            </article>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    <x-card class="{{ $view === 'week' ? 'lg:hidden' : '' }} divide-y divide-line">
        @forelse ($courses as $course)
            <div class="flex flex-col gap-2 border-l-4 px-5 py-4 text-sm sm:flex-row sm:items-center sm:justify-between" style="border-color: {{ $course->group->calendarColor() }}">
                <div>
                    <p class="font-medium">{{ $course->starts_at->translatedFormat('l j F H:i') }}–{{ $course->ends_at->format('H:i') }} · {{ $course->displayTitle() }}</p>
                    <p class="text-ink/55">{{ $course->teacher->full_name }} · {{ $course->group->name }} · {{ $course->room?->code ?? 'Sans salle' }}</p>
                </div>
                <div class="flex gap-2">
                    @can('recordAttendance', $course)
                        <x-button variant="secondary" :href="route('attendance.roll', $course)">Appel</x-button>
                    @endcan
                    @can('update', $course)
                        <x-button variant="ghost" :href="route('courses.edit', $course)">Modifier</x-button>
                    @endcan
                </div>
            </div>
        @empty
            <p class="px-5 py-10 text-sm text-ink/50">Aucun cours sur cette période.</p>
        @endforelse
    </x-card>
@endsection

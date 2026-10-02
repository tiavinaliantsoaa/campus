@extends('layouts.app')

@section('title', 'Vue d\'ensemble')
@section('breadcrumb', 'Vue d\'ensemble')

@section('content')
    <x-page-header eyebrow="Pilotage académique" title="Une vision claire de votre campus." :subtitle="$year->name.' · '.now()->translatedFormat('l j F Y')">
        @if ($canPlan)
            <x-button :href="route('courses.create')">
                <x-icon name="plus" class="h-4 w-4" /> Planifier un cours
            </x-button>
        @endif
    </x-page-header>

    @foreach ($alerts as $alert)
        <x-alert :tone="$alert['tone']">{{ $alert['message'] }}</x-alert>
    @endforeach

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Étudiants inscrits" :value="$metrics['students']" hint="Année {{ $year->name }}" icon="users" />
        <x-stat label="Enseignants" :value="$metrics['teachers']" hint="Équipe pédagogique" icon="academic" />
        <x-stat label="Cours cette semaine" :value="$metrics['courses_this_week']" hint="{{ now()->startOfWeek()->translatedFormat('j M') }} – {{ now()->endOfWeek()->translatedFormat('j M') }}" icon="calendar" />
        <x-stat label="Présence relevée" :value="$metrics['attendance_rate'] === null ? '—' : $metrics['attendance_rate'].' %'" :hint="$metrics['attendance_count'].' pointages · retards inclus'" icon="clipboard" />
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Groupes" :value="$metrics['groups']" icon="groups" />
        <x-stat label="Paiements reçus" :value="\App\Support\Money::format($metrics['payments_received'])" icon="wallet" />
        <x-stat label="Reste à encaisser" :value="\App\Support\Money::format($metrics['payments_pending'])" :hint="$metrics['admissions_pending'].' admission(s) en attente'" icon="chart" />
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(18rem,0.8fr)]">
        <x-card>
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <div>
                    <h2 class="font-semibold">Les cours d'aujourd'hui</h2>
                    <p class="text-sm text-ink/50">{{ $courses->count() }} séance(s) programmée(s)</p>
                </div>
                <a href="{{ route('courses.index') }}" class="text-sm font-medium text-campus">Voir l'agenda</a>
            </div>
            <div class="divide-y divide-line">
                @forelse ($courses as $course)
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div class="w-16 shrink-0 text-sm">
                            <p class="font-medium">{{ $course->starts_at->format('H:i') }}</p>
                            <p class="text-ink/45">{{ $course->ends_at->format('H:i') }}</p>
                        </div>
                        <div class="h-10 w-1 shrink-0 rounded-full bg-campus"></div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $course->displayTitle() }}</p>
                            <p class="truncate text-sm text-ink/55">{{ $course->teacher->full_name }} · {{ $course->group->name }} · {{ $course->room?->code ?? 'Salle à confirmer' }}</p>
                        </div>
                        @can('recordAttendance', $course)
                            <x-button variant="secondary" :href="route('attendance.roll', $course)">Faire l'appel</x-button>
                        @endcan
                    </div>
                @empty
                    <p class="px-5 py-10 text-sm text-ink/50">Aucun cours n'est programmé aujourd'hui.</p>
                @endforelse
            </div>
            <p class="border-t border-line px-5 py-3 text-xs text-ink/45">Heure locale du campus · {{ config('campus.city') }}</p>
        </x-card>

        <div class="flex flex-col gap-4">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-campus to-campus-dark p-6 text-white">
                <p class="text-xs tracking-[0.16em] text-white/70 uppercase">Vie du campus</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight">Chaque présence compte.</h2>
                <p class="mt-3 max-w-xs text-sm text-white/80">Un suivi commun pour les étudiants et les enseignants.</p>
                <x-button variant="secondary" :href="route('attendance.index')" class="mt-6">Faire l'appel <x-icon name="arrow" class="h-4 w-4" /></x-button>
            </section>

            <x-card class="p-5">
                <h2 class="font-semibold">Activité récente</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($activity as $item)
                        <div>
                            <p class="text-sm font-medium">{{ $item['label'] }}</p>
                            <p class="text-sm text-ink/55">{{ $item['detail'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink/50">Les mouvements du campus apparaîtront ici.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </section>
@endsection

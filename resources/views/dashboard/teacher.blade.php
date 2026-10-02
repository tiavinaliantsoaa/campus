@extends('layouts.app')

@section('title', 'Mon enseignement')
@section('breadcrumb', 'Vue d\'ensemble')

@section('content')
    <x-page-header eyebrow="Espace enseignant" title="Bonjour {{ $teacher->first_name }}." :subtitle="now()->translatedFormat('l j F Y')">
        <x-button :href="route('attendance.index')">Faire l'appel</x-button>
        @can('create', App\Models\Assessment::class)
            <x-button variant="secondary" :href="route('assessments.create')">Saisir une évaluation</x-button>
        @endcan
    </x-page-header>

    <section class="grid gap-4 lg:grid-cols-3">
        <x-stat label="Cours aujourd'hui" :value="$courses->count()" icon="calendar" />
        <x-stat label="Groupes" :value="$groups->count()" icon="groups" />
        <x-stat label="Matières" :value="$teacher->subjects->count()" icon="academic" />
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <x-card>
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-semibold">Mes cours du jour</h2>
            </div>
            <div class="divide-y divide-line">
                @forelse ($courses as $course)
                    <div class="flex items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="font-medium">{{ $course->starts_at->format('H:i') }} · {{ $course->displayTitle() }}</p>
                            <p class="text-sm text-ink/55">{{ $course->group->name }} · {{ $course->room?->code ?? 'Sans salle' }}</p>
                        </div>
                        <x-button variant="secondary" :href="route('attendance.roll', $course)">Appel</x-button>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-ink/50">Aucun cours aujourd'hui.</p>
                @endforelse
            </div>
        </x-card>
        <x-card class="p-5">
            <h2 class="font-semibold">Mes groupes</h2>
            <div class="mt-4 space-y-3">
                @forelse ($groups as $assignment)
                    <a href="{{ route('groups.show', $assignment->group) }}" class="block rounded-xl border border-line px-4 py-3 hover:bg-canvas">
                        <p class="font-medium">{{ $assignment->group->name }}</p>
                        <p class="text-sm text-ink/55">{{ $assignment->subject->name }}</p>
                    </a>
                @empty
                    <p class="text-sm text-ink/50">Aucun groupe ne vous est encore affecté.</p>
                @endforelse
            </div>
        </x-card>
    </section>
@endsection

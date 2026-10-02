@extends('layouts.app')

@section('title', 'Espace famille')
@section('breadcrumb', 'Vue d\'ensemble')

@section('content')
    <x-page-header eyebrow="Espace parent" title="Suivi de {{ $student->full_name }}" :subtitle="$year->name">
        @if ($children->count() > 1)
            <form method="POST" action="{{ route('family.select') }}" class="flex items-center gap-2">
                @csrf
                <label class="sr-only" for="student_id">Étudiant</label>
                <select id="student_id" name="student_id" class="rounded-lg border border-line bg-white px-3 py-2 text-sm" onchange="this.form.submit()">
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}" @selected($child->id === $student->id)>{{ $child->full_name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </x-page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Groupe" :value="$enrollment?->group?->code ?? '—'" :hint="$enrollment?->level?->name" icon="groups" />
        <x-stat label="Moyenne" :value="$average === null ? '—' : $average.'/20'" icon="grades" />
        <x-stat label="Absences et retards" :value="$absences" icon="clipboard" />
        <x-stat label="Reste à payer" :value="\App\Support\Money::format($balance['remaining'])" icon="wallet" />
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <x-card>
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-semibold">Cours du jour</h2>
                <a href="{{ route('courses.index') }}" class="text-sm text-campus">Emploi du temps</a>
            </div>
            <div class="divide-y divide-line">
                @forelse ($courses as $course)
                    <div class="px-5 py-4 text-sm">
                        <p class="font-medium">{{ $course->starts_at->format('H:i') }} · {{ $course->displayTitle() }}</p>
                        <p class="text-ink/55">{{ $course->room?->code ?? 'Salle à confirmer' }}</p>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-ink/50">Pas de cours aujourd'hui.</p>
                @endforelse
            </div>
        </x-card>
        <x-card class="space-y-3 p-5">
            <h2 class="font-semibold">Annonces</h2>
            @forelse ($announcements as $announcement)
                <a href="{{ route('announcements.show', $announcement) }}" class="block text-sm">{{ $announcement->title }}</a>
            @empty
                <p class="text-sm text-ink/50">Aucune annonce.</p>
            @endforelse
            <x-button :href="route('inquiries.create')">Écrire à l'établissement</x-button>
            <x-button variant="secondary" :href="route('students.show', $student)">Dossier de l'étudiant</x-button>
        </x-card>
    </section>
@endsection

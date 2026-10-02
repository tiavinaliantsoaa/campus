@extends('layouts.app')

@section('title', 'Mon espace')
@section('breadcrumb', 'Vue d\'ensemble')

@section('content')
    <x-page-header eyebrow="Espace étudiant" :title="$student->full_name" :subtitle="($enrollment?->group?->name ?? 'Sans groupe').' · '.($year->name)">
        <x-button :href="route('students.show', $student)">Ouvrir mon dossier</x-button>
    </x-page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Moyenne" :value="$average === null ? '—' : $average.'/20'" hint="Notes validées" icon="grades" />
        <x-stat label="Absences et retards" :value="$absences" icon="clipboard" />
        <x-stat label="Présence" :value="$attendance['rate'] === null ? '—' : $attendance['rate'].' %'" :hint="$attendance['count'].' pointages'" icon="chart" />
        <x-stat label="Solde restant" :value="\App\Support\Money::format($balance['remaining'])" icon="wallet" />
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <x-card>
            <div class="border-b border-line px-5 py-4"><h2 class="font-semibold">Cours du jour</h2></div>
            <div class="divide-y divide-line">
                @forelse ($courses as $course)
                    <div class="px-5 py-4">
                        <p class="font-medium">{{ $course->starts_at->format('H:i') }}–{{ $course->ends_at->format('H:i') }} · {{ $course->displayTitle() }}</p>
                        <p class="text-sm text-ink/55">{{ $course->teacher->full_name }} · {{ $course->room?->code ?? 'Salle à confirmer' }}</p>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-ink/50">Pas de cours aujourd'hui.</p>
                @endforelse
            </div>
        </x-card>
        <x-card class="p-5">
            <h2 class="font-semibold">Prochaine échéance</h2>
            @if ($nextDue)
                <p class="mt-3 text-2xl font-semibold">{{ \App\Support\Money::format(max((float) $nextDue->amount_due - (float) $nextDue->discount - (float) $nextDue->payments_sum_amount, 0)) }}</p>
                <p class="mt-1 text-sm text-ink/55">{{ $nextDue->label }} · {{ $nextDue->due_on->translatedFormat('j F Y') }}</p>
                <x-button variant="secondary" :href="route('finance.statement', $student)" class="mt-4">Voir les paiements</x-button>
            @else
                <p class="mt-3 text-sm text-ink/50">Aucune échéance ouverte.</p>
            @endif
            <h2 class="mt-8 font-semibold">Annonces</h2>
            <div class="mt-3 space-y-2">
                @forelse ($announcements as $announcement)
                    <a href="{{ route('announcements.show', $announcement) }}" class="block text-sm font-medium text-campus">{{ $announcement->title }}</a>
                @empty
                    <p class="text-sm text-ink/50">Aucune annonce.</p>
                @endforelse
            </div>
        </x-card>
    </section>
@endsection

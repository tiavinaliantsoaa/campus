@extends('layouts.app')

@section('title', $student->full_name)
@section('breadcrumb', 'Étudiants')

@section('content')
    @php
        $tabs = [
            'overview' => 'Vue générale',
            'information' => 'Informations',
            'schooling' => 'Scolarité',
            'timetable' => 'Emploi du temps',
            'attendance' => 'Présence',
            'grades' => 'Notes',
            'payments' => 'Paiements',
            'documents' => 'Documents',
            'history' => 'Historique',
        ];
    @endphp

    <x-page-header :eyebrow="$student->matricule" :title="$student->full_name" :subtitle="($enrollment?->group?->name ?? 'Sans groupe').' · '.$year->name">
        <x-badge :tone="$student->status->tone()">{{ $student->status->label() }}</x-badge>
        @can('update', $student)
            <x-button variant="secondary" :href="route('students.edit', $student)">Modifier</x-button>
            <x-button variant="secondary" type="button" data-confirm data-action="{{ route('students.archive', $student) }}" data-method="POST" data-message="Archiver cet étudiant ?">Archiver</x-button>
        @endcan
        @can('delete', $student)
            <x-button variant="danger" type="button" data-confirm data-action="{{ route('students.destroy', $student) }}" data-method="DELETE" data-message="Supprimer ce dossier étudiant ?">Supprimer</x-button>
        @endcan
    </x-page-header>

    <div class="flex gap-2 overflow-x-auto">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('students.show', [$student, 'tab' => $key]) }}" class="shrink-0 rounded-full px-4 py-2 text-sm {{ $tab === $key ? 'bg-ink text-white' : 'border border-line bg-white text-ink/70' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <section class="grid gap-4 sm:grid-cols-3">
            <x-stat label="Moyenne" :value="$average === null ? '—' : $average.'/20'" icon="grades" />
            <x-stat label="Solde restant" :value="\App\Support\Money::format($balance['remaining'])" icon="wallet" />
            <x-stat label="Groupe" :value="$enrollment?->group?->code ?? '—'" :hint="$enrollment?->level?->name" icon="groups" />
        </section>
        <x-card class="p-5">
            <p class="text-sm text-ink/60">{{ $student->email ?: 'E-mail non renseigné' }} · {{ $student->phone ?: 'Téléphone non renseigné' }} · {{ $student->city ?: config('campus.city') }}</p>
        </x-card>
    @endif

    @if ($tab === 'information')
        <x-card class="grid gap-4 p-5 sm:grid-cols-2">
            <p><span class="block text-xs text-ink/45">Naissance</span>{{ $student->birth_date?->translatedFormat('j F Y') ?? '—' }}</p>
            <p><span class="block text-xs text-ink/45">Genre</span>{{ $student->gender?->label() ?? '—' }}</p>
            <p><span class="block text-xs text-ink/45">Adresse</span>{{ $student->address ?: '—' }}</p>
            <p><span class="block text-xs text-ink/45">Compte</span>{{ $student->user?->email ?? 'Aucun accès' }}</p>
            <div class="sm:col-span-2">
                <h3 class="font-medium">Urgences</h3>
                @forelse ($student->emergencyContacts as $contact)
                    <p class="mt-2 text-sm">{{ $contact->name }} · {{ $contact->relationship }} · {{ $contact->phone }}</p>
                @empty
                    <p class="mt-2 text-sm text-ink/50">Aucun contact d'urgence.</p>
                @endforelse
            </div>
            <div class="sm:col-span-2">
                <h3 class="font-medium">Responsables</h3>
                @forelse ($student->guardians as $guardian)
                    <p class="mt-2 text-sm">{{ $guardian->full_name }} · {{ $guardian->pivot->relationship }} · {{ $guardian->email }}</p>
                @empty
                    <p class="mt-2 text-sm text-ink/50">Aucun responsable lié.</p>
                @endforelse
            </div>
        </x-card>
    @endif

    @if ($tab === 'schooling')
        <x-card class="p-5">
            <p class="text-sm">Niveau {{ $enrollment?->level?->name ?? '—' }}</p>
            <p class="mt-2 text-sm">Groupe {{ $enrollment?->group?->name ?? '—' }}</p>
            <p class="mt-2 text-sm">Inscrit le {{ $enrollment?->enrolled_on?->translatedFormat('j F Y') ?? '—' }}</p>
            @if ($student->notes)
                <p class="mt-4 text-sm text-ink/70">{{ $student->notes }}</p>
            @endif
        </x-card>
    @endif

    @if ($tab === 'timetable')
        <x-card class="divide-y divide-line">
            @forelse ($courses as $course)
                <div class="px-5 py-4 text-sm">
                    <p class="font-medium">{{ $course->starts_at->translatedFormat('l j F') }} · {{ $course->starts_at->format('H:i') }}–{{ $course->ends_at->format('H:i') }}</p>
                    <p class="text-ink/60">{{ $course->displayTitle() }} · {{ $course->teacher->full_name }} · {{ $course->room?->code ?? 'Sans salle' }}</p>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucun cours pour ce groupe.</p>
            @endforelse
        </x-card>
    @endif

    @if ($tab === 'attendance')
        <x-card class="divide-y divide-line">
            @forelse ($attendance as $record)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>{{ $record->course->starts_at->translatedFormat('j M Y H:i') }} · {{ $record->course->subject->name }}</span>
                    <x-badge :tone="$record->status->tone()">{{ $record->status->label() }}</x-badge>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucune présence enregistrée.</p>
            @endforelse
        </x-card>
    @endif

    @if ($tab === 'grades')
        <x-card>
            <div class="border-b border-line px-5 py-4 text-sm">Moyenne validée : <span class="font-semibold">{{ $average === null ? '—' : $average.'/20' }}</span></div>
            <div class="divide-y divide-line">
                @forelse ($grades as $grade)
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <span>{{ $grade->assessment->subject->name }} · {{ $grade->assessment->title }}</span>
                        <span class="font-medium">{{ $grade->score }}/{{ $grade->assessment->max_score }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-ink/50">Aucune note validée.</p>
                @endforelse
            </div>
        </x-card>
    @endif

    @if ($tab === 'payments')
        <x-card class="divide-y divide-line">
            @forelse ($installments as $installment)
                <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <div>
                        <p class="font-medium">{{ $installment->label }}</p>
                        <p class="text-ink/50">Échéance {{ $installment->due_on->translatedFormat('j F Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p>{{ \App\Support\Money::format($finance->remaining($installment)) }} restants</p>
                        <x-badge :tone="$finance->status($installment) === 'paid' ? 'success' : 'warning'">{{ $finance->statusLabel($finance->status($installment)) }}</x-badge>
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucune échéance.</p>
            @endforelse
        </x-card>
        @can('viewAny', App\Models\Payment::class)
            <x-button :href="route('finance.statement', $student)">Relevé complet</x-button>
        @endcan
    @endif

    @if ($tab === 'documents')
        <x-card class="divide-y divide-line">
            @forelse ($documents as $document)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>{{ $document->title }}</span>
                    <a href="{{ route('documents.download', $document) }}" class="text-campus">Télécharger</a>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucun document.</p>
            @endforelse
        </x-card>
        @can('create', App\Models\Document::class)
            <x-button :href="route('documents.create', ['student' => $student->id])">Ajouter un document</x-button>
        @endcan
    @endif

    @if ($tab === 'history')
        <x-card class="divide-y divide-line">
            @foreach ($student->enrollments as $item)
                <div class="px-5 py-3 text-sm">
                    <p class="font-medium">{{ $item->academicYear->name }}</p>
                    <p class="text-ink/55">{{ $item->level?->name ?? 'Niveau non précisé' }} · {{ $item->group?->name ?? 'Sans groupe' }} · {{ $item->status }}</p>
                </div>
            @endforeach
        </x-card>
    @endif
@endsection

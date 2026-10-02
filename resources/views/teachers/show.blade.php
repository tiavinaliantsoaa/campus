@extends('layouts.app')
@section('title', $teacher->full_name)
@section('breadcrumb', 'Enseignants')
@section('content')
    <x-page-header :title="$teacher->full_name" :subtitle="$teacher->employee_number.' · '.$year->name">
        @can('update', $teacher)
            <x-button variant="secondary" :href="route('teachers.edit', $teacher)">Modifier</x-button>
        @endcan
        @can('delete', $teacher)
            <x-button variant="danger" type="button" data-confirm data-action="{{ route('teachers.destroy', $teacher) }}" data-method="DELETE" data-message="Supprimer cet enseignant ?">Supprimer</x-button>
        @endcan
    </x-page-header>
    <section class="grid gap-4 lg:grid-cols-2">
        <x-card class="space-y-2 p-5 text-sm">
            <p>{{ $teacher->email ?: 'E-mail non renseigné' }}</p>
            <p>{{ $teacher->phone ?: 'Téléphone non renseigné' }}</p>
            <p>Matières : {{ $teacher->subjects->pluck('name')->join(', ') ?: '—' }}</p>
            <p>Statut : {{ $teacher->status->label() }}</p>
            @if ($teacher->biography)<p class="text-ink/70">{{ $teacher->biography }}</p>@endif
        </x-card>
        <x-card class="p-5">
            <h2 class="font-semibold">Groupes</h2>
            @forelse ($assignments as $assignment)
                <p class="mt-2 text-sm">{{ $assignment->group->name }} · {{ $assignment->subject->name }}</p>
            @empty
                <p class="mt-2 text-sm text-ink/50">Aucune affectation cette année.</p>
            @endforelse
        </x-card>
        <x-card class="p-5 lg:col-span-2">
            <h2 class="font-semibold">Emploi du temps</h2>
            @forelse ($courses as $course)
                <p class="mt-2 text-sm">{{ $course->starts_at->translatedFormat('D j M H:i') }} · {{ $course->displayTitle() }} · {{ $course->group->name }} · {{ $course->room?->code ?? 'Sans salle' }}</p>
            @empty
                <p class="mt-2 text-sm text-ink/50">Aucun cours.</p>
            @endforelse
        </x-card>
        <x-card class="p-5 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Documents</h2>
                @can('create', App\Models\Document::class)
                    <a class="text-sm text-campus" href="{{ route('documents.create', ['teacher' => $teacher->id]) }}">Ajouter</a>
                @endcan
            </div>
            @forelse ($teacher->documents as $document)
                <p class="mt-2 text-sm"><a class="text-campus" href="{{ route('documents.download', $document) }}">{{ $document->title }}</a></p>
            @empty
                <p class="mt-2 text-sm text-ink/50">Aucun document.</p>
            @endforelse
        </x-card>
    </section>
@endsection

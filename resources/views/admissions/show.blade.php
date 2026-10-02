@extends('layouts.app')
@section('title', $applicant->full_name)
@section('breadcrumb', 'Admissions')
@section('content')
    <x-page-header :title="$applicant->full_name" :subtitle="$applicant->email">
        <x-badge>{{ $applicant->admissionStatus->name }}</x-badge>
        @can('update', $applicant)
            <x-button variant="secondary" :href="route('applicants.edit', $applicant)">Modifier</x-button>
        @endcan
    </x-page-header>
    <x-card class="space-y-2 p-5 text-sm">
        <p>Téléphone : {{ $applicant->phone ?: '—' }}</p>
        <p>Niveau : {{ $applicant->level?->name ?? '—' }}</p>
        <p>Naissance : {{ $applicant->birth_date?->translatedFormat('j F Y') ?? '—' }}</p>
        <p>Adresse : {{ $applicant->address ?: '—' }}</p>
        @if ($applicant->motivation)<p class="whitespace-pre-line text-ink/70">{{ $applicant->motivation }}</p>@endif
        @if ($applicant->student)<p>Étudiant lié : <a class="text-campus" href="{{ route('students.show', $applicant->student) }}">{{ $applicant->student->full_name }}</a></p>@endif
    </x-card>
    @can('update', $applicant)
        <form method="POST" action="{{ route('applicants.status', $applicant) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <x-select name="admission_status_id" label="Changer le statut" :value="$applicant->admission_status_id" :options="$statuses->mapWithKeys(fn ($status) => [$status->id => $status->name])->all()" placeholder="Statut" />
            <x-button variant="secondary">Mettre à jour</x-button>
        </form>
        @if (! $applicant->student_id && $applicant->admissionStatus->converts_to_student)
            <form method="POST" action="{{ route('applicants.convert', $applicant) }}" class="grid max-w-xl gap-3 rounded-2xl border border-line bg-white p-5">
                @csrf
                <h2 class="font-semibold">Convertir en étudiant</h2>
                <x-select name="level_id" label="Niveau" :value="$applicant->level_id" :options="$levels->mapWithKeys(fn ($level) => [$level->id => $level->name])->all()" />
                <x-select name="student_group_id" label="Groupe" :options="$groups->mapWithKeys(fn ($group) => [$group->id => $group->name])->all()" />
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="create_account" value="1"> Créer un accès étudiant</label>
                <x-button>Admettre et inscrire</x-button>
            </form>
        @endif
    @endcan
@endsection

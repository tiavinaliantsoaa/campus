@extends('layouts.app')
@section('title', $applicant->exists ? 'Modifier la candidature' : 'Nouvelle candidature')
@section('breadcrumb', 'Admissions')
@section('content')
    <x-page-header :title="$applicant->exists ? $applicant->full_name : 'Nouvelle candidature'" :subtitle="$year->name" />
    <form method="POST" action="{{ $applicant->exists ? route('applicants.update', $applicant) : route('applicants.store') }}" class="max-w-3xl">
        @csrf
        @if ($applicant->exists) @method('PUT') @endif
        <x-card class="grid gap-4 p-5 sm:grid-cols-2">
            <x-field name="first_name" label="Prénom" :value="old('first_name', $applicant->first_name)" required />
            <x-field name="last_name" label="Nom" :value="old('last_name', $applicant->last_name)" required />
            <x-field name="email" type="email" label="E-mail" :value="old('email', $applicant->email)" required />
            <x-field name="phone" label="Téléphone" :value="old('phone', $applicant->phone)" />
            <x-field name="birth_date" type="date" label="Naissance" :value="old('birth_date', $applicant->birth_date?->toDateString())" />
            <x-select name="level_id" label="Niveau demandé" :value="old('level_id', $applicant->level_id)" :options="$levels->mapWithKeys(fn ($level) => [$level->id => $level->name])->all()" />
            <x-select name="admission_status_id" label="Statut" :value="old('admission_status_id', $applicant->admission_status_id ?? $statuses->firstWhere('slug', 'submitted')?->id)" :options="$statuses->mapWithKeys(fn ($status) => [$status->id => $status->name])->all()" placeholder="Statut" />
            <x-field name="address" label="Adresse" :value="old('address', $applicant->address)" />
            <div class="sm:col-span-2"><x-textarea name="motivation" label="Motivation" :value="old('motivation', $applicant->motivation)" /></div>
            <div class="sm:col-span-2"><x-textarea name="notes" label="Notes internes" :value="old('notes', $applicant->notes)" /></div>
            <div class="sm:col-span-2"><x-button>Enregistrer</x-button></div>
        </x-card>
    </form>
@endsection

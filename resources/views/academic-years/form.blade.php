@extends('layouts.app')
@section('title', $year->exists ? 'Modifier l\'année' : 'Nouvelle année')
@section('breadcrumb', 'Administration')
@section('content')
    <x-page-header :title="$year->exists ? $year->name : 'Nouvelle année académique'" />
    <form method="POST" action="{{ $year->exists ? route('academic-years.update', $year) : route('academic-years.store') }}" class="max-w-3xl">
        @csrf
        @if ($year->exists) @method('PUT') @endif
        <x-card class="space-y-4 p-5">
            <x-field name="name" label="Nom" :value="old('name', $year->name)" placeholder="2026-2027" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="starts_on" type="date" label="Début" :value="old('starts_on', $year->starts_on?->toDateString())" required />
                <x-field name="ends_on" type="date" label="Fin" :value="old('ends_on', $year->ends_on?->toDateString())" required />
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ranking_enabled" value="1" @checked(old('ranking_enabled', $year->ranking_enabled))> Activer le classement</label>
            <h2 class="font-semibold">Semestres</h2>
            @for ($index = 0; $index < 2; $index++)
                @php $semester = $year->relationLoaded('semesters') ? $year->semesters->get($index) : null; @endphp
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-field name="semesters[{{ $index }}][name]" label="Nom" :value="old('semesters.'.$index.'.name', $semester?->name)" />
                    <x-field name="semesters[{{ $index }}][starts_on]" type="date" label="Début" :value="old('semesters.'.$index.'.starts_on', $semester?->starts_on?->toDateString())" />
                    <x-field name="semesters[{{ $index }}][ends_on]" type="date" label="Fin" :value="old('semesters.'.$index.'.ends_on', $semester?->ends_on?->toDateString())" />
                </div>
            @endfor
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

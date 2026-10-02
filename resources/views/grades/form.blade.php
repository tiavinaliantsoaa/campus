@extends('layouts.app')
@section('title', 'Nouvelle évaluation')
@section('breadcrumb', 'Notes & résultats')
@section('content')
    <x-page-header title="Nouvelle évaluation" :subtitle="$year->name" />
    <form method="POST" action="{{ route('assessments.store') }}" class="max-w-3xl">
        @csrf
        <x-card class="grid gap-4 p-5 sm:grid-cols-2">
            <x-field name="title" label="Intitulé" :value="old('title')" required />
            <x-select name="type" label="Type" :value="old('type', 'exam')" :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" placeholder="Type" />
            <x-select name="subject_id" label="Matière" :value="old('subject_id')" :options="$subjects->mapWithKeys(fn ($subject) => [$subject->id => $subject->name])->all()" />
            <x-select name="teacher_id" label="Enseignant" :value="old('teacher_id', $defaultTeacher)" :options="$teachers->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->full_name])->all()" />
            <x-select name="student_group_id" label="Groupe" :value="old('student_group_id')" :options="$groups->mapWithKeys(fn ($group) => [$group->id => $group->name])->all()" />
            <x-select name="semester_id" label="Semestre" :value="old('semester_id')" :options="$semesters->mapWithKeys(fn ($semester) => [$semester->id => $semester->name])->all()" />
            <x-field name="coefficient" type="number" step="0.1" label="Coefficient" :value="old('coefficient', '1')" required />
            <x-field name="max_score" type="number" step="0.1" label="Note maximale" :value="old('max_score', '20')" required />
            <x-field name="assessed_on" type="date" label="Date" :value="old('assessed_on', now()->toDateString())" required />
            <div class="sm:col-span-2"><x-button>Créer et saisir les notes</x-button></div>
        </x-card>
    </form>
@endsection

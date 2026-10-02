@extends('layouts.app')
@section('title', $course->exists ? 'Modifier un cours' : 'Planifier un cours')
@section('breadcrumb', 'Emploi du temps')
@section('content')
    <x-page-header :title="$course->exists ? 'Modifier le cours' : 'Planifier un cours'" :subtitle="$year->name" />
    <form method="POST" action="{{ $course->exists ? route('courses.update', $course) : route('courses.store') }}" class="max-w-3xl">
        @csrf
        @if ($course->exists) @method('PUT') @endif
        <x-card class="grid gap-4 p-5 sm:grid-cols-2">
            <x-select name="subject_id" label="Matière" :value="old('subject_id', $course->subject_id)" :options="$subjects->mapWithKeys(fn ($subject) => [$subject->id => $subject->name])->all()" />
            <x-select name="teacher_id" label="Enseignant" :value="old('teacher_id', $course->teacher_id)" :options="$teachers->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->full_name])->all()" />
            <x-select name="student_group_id" label="Groupe" :value="old('student_group_id', $course->student_group_id)" :options="$groups->mapWithKeys(fn ($group) => [$group->id => $group->name])->all()" />
            <x-select name="room_id" label="Salle" :value="old('room_id', $course->room_id)" :options="$rooms->mapWithKeys(fn ($room) => [$room->id => $room->label()])->all()" />
            <x-select name="semester_id" label="Semestre" :value="old('semester_id', $course->semester_id)" :options="$semesters->mapWithKeys(fn ($semester) => [$semester->id => $semester->name])->all()" />
            <x-field name="title" label="Intitulé affiché" :value="old('title', $course->title)" />
            <x-field name="starts_at" type="datetime-local" label="Début" :value="old('starts_at', $course->starts_at?->format('Y-m-d\TH:i'))" required />
            <x-field name="ends_at" type="datetime-local" label="Fin" :value="old('ends_at', $course->ends_at?->format('Y-m-d\TH:i'))" required />
            @unless ($course->exists)
                <x-field name="repeat_until" type="date" label="Répéter chaque semaine jusqu'au" :value="old('repeat_until')" />
            @else
                <x-select name="status" label="Statut" :value="old('status', $course->status?->value)" :options="collect(App\Enums\CourseStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" placeholder="Statut" />
            @endunless
            <div class="sm:col-span-2"><x-textarea name="notes" label="Notes" :value="old('notes', $course->notes)" /></div>
            <div class="flex gap-2 sm:col-span-2">
                <x-button>Enregistrer</x-button>
                @if ($course->exists)
                    <x-button variant="danger" type="button" data-confirm data-action="{{ route('courses.destroy', $course) }}" data-method="DELETE" data-message="Supprimer ce cours ?">Supprimer</x-button>
                @endif
            </div>
        </x-card>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Déposer un document')
@section('breadcrumb', 'Documents')
@section('content')
    <x-page-header title="Déposer un document" />
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="max-w-xl">
        @csrf
        <x-card class="space-y-4 p-5">
            <x-field name="title" label="Titre" :value="old('title')" required />
            <x-select name="category" label="Catégorie" :value="old('category', $student ? 'student' : ($teacher ? 'teacher' : 'administrative'))" :options="collect($categories)->mapWithKeys(fn ($category) => [$category->value => $category->label()])->all()" placeholder="Catégorie" />
            <x-textarea name="description" label="Description" :value="old('description')" />
            <x-field name="file" type="file" label="Fichier" required />
            @if ($student)<input type="hidden" name="student_id" value="{{ $student->id }}"><p class="text-sm text-ink/60">Lié à {{ $student->full_name }}</p>@endif
            @if ($teacher)<input type="hidden" name="teacher_id" value="{{ $teacher->id }}"><p class="text-sm text-ink/60">Lié à {{ $teacher->full_name }}</p>@endif
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

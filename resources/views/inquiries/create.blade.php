@extends('layouts.app')
@section('title', 'Nouveau message')
@section('breadcrumb', 'Communication')
@section('content')
    <x-page-header title="Écrire à l'établissement" />
    <form method="POST" action="{{ route('inquiries.store') }}" class="max-w-xl">
        @csrf
        <x-card class="space-y-4 p-5">
            <x-select name="student_id" label="Étudiant concerné" :value="old('student_id')" :options="$students->mapWithKeys(fn ($student) => [$student->id => $student->full_name])->all()" />
            <x-field name="subject" label="Sujet" :value="old('subject')" required />
            <x-textarea name="body" label="Message" :value="old('body')" required />
            <x-button>Envoyer</x-button>
        </x-card>
    </form>
@endsection

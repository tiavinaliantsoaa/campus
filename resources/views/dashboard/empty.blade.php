@extends('layouts.app')

@section('title', 'Campus')
@section('breadcrumb', 'Vue d\'ensemble')

@section('content')
    <x-empty title="Le campus n'est pas encore configuré." description="Créez une année académique pour commencer à inscrire les étudiants, planifier les cours et suivre la scolarité.">
        @can('create', App\Models\AcademicYear::class)
            <x-button :href="route('academic-years.create')">Créer l'année académique</x-button>
        @endcan
    </x-empty>
@endsection

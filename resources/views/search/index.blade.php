@extends('layouts.app')
@section('title', 'Recherche')
@section('breadcrumb', 'Recherche')
@section('content')
    <x-page-header title="Recherche" :subtitle="$term !== '' ? 'Résultats pour « '.$term.' »' : 'Saisissez au moins deux caractères.'" />
    <form method="GET" class="md:hidden"><input type="search" name="q" value="{{ $term }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm"></form>
    <section class="grid gap-4 lg:grid-cols-3">
        <x-card class="p-5"><h2 class="font-semibold">Étudiants</h2>@forelse ($students as $student)<a class="mt-2 block text-sm" href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a>@empty<p class="mt-2 text-sm text-ink/50">Aucun résultat.</p>@endforelse</x-card>
        <x-card class="p-5"><h2 class="font-semibold">Enseignants</h2>@forelse ($teachers as $teacher)<a class="mt-2 block text-sm" href="{{ route('teachers.show', $teacher) }}">{{ $teacher->full_name }}</a>@empty<p class="mt-2 text-sm text-ink/50">Aucun résultat.</p>@endforelse</x-card>
        <x-card class="p-5"><h2 class="font-semibold">Groupes</h2>@forelse ($groups as $group)<a class="mt-2 block text-sm" href="{{ route('groups.show', $group) }}">{{ $group->name }}</a>@empty<p class="mt-2 text-sm text-ink/50">Aucun résultat.</p>@endforelse</x-card>
    </section>
@endsection

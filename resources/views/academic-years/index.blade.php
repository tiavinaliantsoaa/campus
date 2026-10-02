@extends('layouts.app')
@section('title', 'Années académiques')
@section('breadcrumb', 'Administration')
@section('content')
    <x-page-header title="Années académiques" subtitle="Une seule année peut être active.">
        <x-button :href="route('academic-years.create')">Nouvelle année</x-button>
        <x-button variant="secondary" :href="route('users.index')">Comptes</x-button>
    </x-page-header>
    <x-card class="divide-y divide-line">
        @foreach ($years as $year)
            <a href="{{ route('academic-years.show', $year) }}" class="flex items-center justify-between px-5 py-4 text-sm hover:bg-canvas">
                <span><span class="font-medium">{{ $year->name }}</span><span class="block text-ink/50">{{ $year->groups_count }} groupes · {{ $year->enrollments_count }} inscriptions</span></span>
                <x-badge :tone="$year->status->value === 'active' ? 'success' : 'neutral'">{{ $year->status->label() }}</x-badge>
            </a>
        @endforeach
    </x-card>
@endsection

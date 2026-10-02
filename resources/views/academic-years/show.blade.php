@extends('layouts.app')
@section('title', $year->name)
@section('breadcrumb', 'Administration')
@section('content')
    <x-page-header :title="$year->name" :subtitle="$year->starts_on->translatedFormat('j F Y').' — '.$year->ends_on->translatedFormat('j F Y')">
        <x-badge :tone="$year->status->value === 'active' ? 'success' : 'neutral'">{{ $year->status->label() }}</x-badge>
        <x-button variant="secondary" :href="route('academic-years.edit', $year)">Modifier</x-button>
    </x-page-header>
    <x-card class="space-y-2 p-5 text-sm">
        <p>Classement {{ $year->ranking_enabled ? 'activé' : 'désactivé' }}</p>
        @foreach ($year->semesters as $semester)
            <p>{{ $semester->name }} · {{ $semester->starts_on->translatedFormat('j M Y') }} – {{ $semester->ends_on->translatedFormat('j M Y') }}</p>
        @endforeach
    </x-card>
    <div class="flex flex-wrap gap-2">
        @if ($year->status->value !== 'active' && $year->status->value !== 'archived')
            <form method="POST" action="{{ route('academic-years.activate', $year) }}">@csrf<x-button>Activer</x-button></form>
        @endif
        @if ($year->status->value === 'active')
            <form method="POST" action="{{ route('academic-years.close', $year) }}">@csrf<x-button variant="secondary">Clôturer</x-button></form>
        @endif
        @if ($year->status->value === 'closed')
            <form method="POST" action="{{ route('academic-years.archive', $year) }}">@csrf<x-button variant="danger">Archiver</x-button></form>
        @endif
    </div>
@endsection

@extends('layouts.app')
@section('title', $announcement->title)
@section('breadcrumb', 'Communication')
@section('content')
    <x-page-header :title="$announcement->title" :subtitle="$announcement->author->name.' · '.($announcement->published_at?->translatedFormat('j F Y H:i') ?? 'Brouillon')">
        @can('update', $announcement)
            <x-button variant="secondary" :href="route('announcements.edit', $announcement)">Modifier</x-button>
        @endcan
        @can('delete', $announcement)
            <x-button variant="danger" type="button" data-confirm data-action="{{ route('announcements.destroy', $announcement) }}" data-method="DELETE" data-message="Supprimer cette annonce ?">Supprimer</x-button>
        @endcan
    </x-page-header>
    <x-card class="p-6">
        <p class="whitespace-pre-line text-sm leading-6">{{ $announcement->body }}</p>
    </x-card>
@endsection

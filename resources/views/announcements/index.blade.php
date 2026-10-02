@extends('layouts.app')
@section('title', 'Communication')
@section('breadcrumb', 'Communication')
@section('content')
    <x-page-header title="Communication">
        @if ($manage)
            <x-button :href="route('announcements.create')">Nouvelle annonce</x-button>
        @endif
        <x-button variant="secondary" :href="route('inquiries.index')">Messages</x-button>
    </x-page-header>
    <x-card class="divide-y divide-line">
        @forelse ($announcements as $announcement)
            <a href="{{ route('announcements.show', $announcement) }}" class="block px-5 py-4 hover:bg-canvas">
                <p class="font-medium">{{ $announcement->title }}</p>
                <p class="text-sm text-ink/50">{{ $announcement->audience->label() }} · {{ $announcement->published_at?->translatedFormat('j F Y') ?? 'Brouillon' }}</p>
            </a>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucune annonce.</p>
        @endforelse
        {{ $announcements->links() }}
    </x-card>
@endsection

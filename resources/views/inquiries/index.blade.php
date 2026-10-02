@extends('layouts.app')
@section('title', 'Messages')
@section('breadcrumb', 'Communication')
@section('content')
    <x-page-header title="Messages">
        @can('create', App\Models\Inquiry::class)
            <x-button :href="route('inquiries.create')">Nouveau message</x-button>
        @endcan
        <x-button variant="secondary" :href="route('announcements.index')">Annonces</x-button>
    </x-page-header>
    <x-card class="divide-y divide-line">
        @forelse ($inquiries as $inquiry)
            <a href="{{ route('inquiries.show', $inquiry) }}" class="flex items-center justify-between px-5 py-4 text-sm hover:bg-canvas">
                <span><span class="font-medium">{{ $inquiry->subject }}</span><span class="block text-ink/50">{{ $inquiry->author->name }} @if($inquiry->student) · {{ $inquiry->student->full_name }} @endif</span></span>
                <x-badge :tone="$inquiry->status->tone()">{{ $inquiry->status->label() }}</x-badge>
            </a>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucun message.</p>
        @endforelse
        {{ $inquiries->links() }}
    </x-card>
@endsection

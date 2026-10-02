@extends('layouts.app')
@section('title', $inquiry->subject)
@section('breadcrumb', 'Communication')
@section('content')
    <x-page-header :title="$inquiry->subject" :subtitle="$inquiry->author->name.($inquiry->student ? ' · '.$inquiry->student->full_name : '')">
        <x-badge :tone="$inquiry->status->tone()">{{ $inquiry->status->label() }}</x-badge>
    </x-page-header>
    <x-card class="p-5"><p class="whitespace-pre-line text-sm">{{ $inquiry->body }}</p></x-card>
    <div class="space-y-3">
        @foreach ($inquiry->replies as $reply)
            <x-card class="p-4 text-sm">
                <p class="font-medium">{{ $reply->user->name }}</p>
                <p class="mt-2 whitespace-pre-line">{{ $reply->body }}</p>
            </x-card>
        @endforeach
    </div>
    @can('reply', $inquiry)
        <form method="POST" action="{{ route('inquiries.reply', $inquiry) }}" class="max-w-xl space-y-3">
            @csrf
            <x-textarea name="body" label="Réponse" />
            <x-button>Répondre</x-button>
        </form>
    @endcan
    @if (auth()->user()->hasPermission('communication.manage') && $inquiry->status->value !== 'closed')
        <form method="POST" action="{{ route('inquiries.close', $inquiry) }}">@csrf<x-button variant="secondary">Clôturer</x-button></form>
    @endif
@endsection

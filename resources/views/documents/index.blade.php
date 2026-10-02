@extends('layouts.app')
@section('title', 'Documents')
@section('breadcrumb', 'Documents')
@section('content')
    <x-page-header title="Documents">
        @can('create', App\Models\Document::class)
            <x-button :href="route('documents.create')">Déposer un document</x-button>
        @endcan
    </x-page-header>
    <x-card>
        <form method="GET" class="grid gap-3 border-b border-line p-4 md:grid-cols-3">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Titre" class="rounded-lg border border-line px-3 py-2 text-sm">
            <select name="category" class="rounded-lg border border-line px-3 py-2 text-sm"><option value="">Catégorie</option>@foreach ($categories as $category)<option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>@endforeach</select>
            <x-button type="submit" variant="secondary">Filtrer</x-button>
        </form>
        <div class="divide-y divide-line">
            @forelse ($documents as $document)
                <div class="flex flex-col gap-2 px-5 py-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium">{{ $document->title }}</p>
                        <p class="text-ink/50">{{ $document->category->label() }} · {{ $document->original_name }} @if($document->isArchived()) · archivé @endif</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('documents.download', $document) }}" class="text-campus">Télécharger</a>
                        @can('archive', $document)
                            <form method="POST" action="{{ route('documents.archive', $document) }}">@csrf<button class="text-ink/60">{{ $document->isArchived() ? 'Restaurer' : 'Archiver' }}</button></form>
                        @endcan
                        @can('delete', $document)
                            <button type="button" class="text-campus" data-confirm data-action="{{ route('documents.destroy', $document) }}" data-method="DELETE" data-message="Supprimer ce document ?">Supprimer</button>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucun document.</p>
            @endforelse
        </div>
        {{ $documents->links() }}
    </x-card>
@endsection

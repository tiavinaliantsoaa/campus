@extends('layouts.app')
@section('title', 'Niveaux')
@section('breadcrumb', 'Niveaux')
@section('content')
    <x-page-header title="Niveaux" subtitle="Structure des parcours">
        <x-button variant="secondary" :href="route('groups.index')">Retour aux groupes</x-button>
    </x-page-header>
    @if (auth()->user()->hasPermission('groups.manage'))
        <form method="POST" action="{{ route('levels.store') }}" class="grid gap-3 rounded-2xl border border-line bg-white p-4 md:grid-cols-4">
            @csrf
            <x-field name="name" label="Nom" :value="old('name')" required />
            <x-field name="code" label="Code" :value="old('code')" required />
            <x-field name="sort_order" type="number" label="Ordre" :value="old('sort_order', 0)" />
            <div class="self-end"><x-button>Ajouter</x-button></div>
        </form>
    @endif
    <x-card class="divide-y divide-line">
        @forelse ($levels as $level)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <span>{{ $level->name }} <span class="text-ink/45">{{ $level->code }}</span></span>
                @if (auth()->user()->hasPermission('groups.manage'))
                    <button type="button" data-confirm data-action="{{ route('levels.destroy', $level) }}" data-method="DELETE" data-message="Supprimer ce niveau ?" class="text-campus">Supprimer</button>
                @endif
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucun niveau.</p>
        @endforelse
    </x-card>
@endsection

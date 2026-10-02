@extends('layouts.app')
@section('title', 'Matières')
@section('breadcrumb', 'Matières')
@section('content')
    <x-page-header title="Matières">
        <x-button variant="secondary" :href="route('groups.index')">Retour aux groupes</x-button>
    </x-page-header>
    @if (auth()->user()->hasPermission('groups.manage'))
        <form method="POST" action="{{ route('subjects.store') }}" class="grid gap-3 rounded-2xl border border-line bg-white p-4 md:grid-cols-3">
            @csrf
            <x-field name="name" label="Nom" :value="old('name')" required />
            <x-field name="code" label="Code" :value="old('code')" required />
            <div class="self-end"><x-button>Ajouter</x-button></div>
        </form>
    @endif
    <x-card>
        <div class="divide-y divide-line">
            @foreach ($subjects as $subject)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>{{ $subject->name }} · {{ $subject->code }}</span>
                    @if (auth()->user()->hasPermission('groups.manage'))
                        <button type="button" class="text-campus" data-confirm data-action="{{ route('subjects.destroy', $subject) }}" data-method="DELETE" data-message="Supprimer cette matière ?">Supprimer</button>
                    @endif
                </div>
            @endforeach
        </div>
        {{ $subjects->links() }}
    </x-card>
@endsection

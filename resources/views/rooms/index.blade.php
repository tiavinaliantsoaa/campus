@extends('layouts.app')
@section('title', 'Salles')
@section('breadcrumb', 'Salles')
@section('content')
    <x-page-header title="Salles" />
    @if (auth()->user()->hasPermission('timetable.manage'))
        <form method="POST" action="{{ route('rooms.store') }}" class="grid gap-3 rounded-2xl border border-line bg-white p-4 md:grid-cols-4">
            @csrf
            <x-field name="name" label="Nom" :value="old('name')" required />
            <x-field name="code" label="Code" :value="old('code')" required />
            <x-field name="building" label="Bâtiment" :value="old('building')" />
            <x-field name="capacity" type="number" label="Capacité" :value="old('capacity')" />
            <div class="md:col-span-4"><x-button>Ajouter</x-button></div>
        </form>
    @endif
    <x-card class="divide-y divide-line">
        @forelse ($rooms as $room)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <span>{{ $room->name }} · {{ $room->label() }}</span>
                @if (auth()->user()->hasPermission('timetable.manage'))
                    <button type="button" class="text-campus" data-confirm data-action="{{ route('rooms.destroy', $room) }}" data-method="DELETE" data-message="Supprimer cette salle ?">Supprimer</button>
                @endif
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucune salle.</p>
        @endforelse
    </x-card>
@endsection

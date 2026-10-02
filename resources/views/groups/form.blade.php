@extends('layouts.app')
@section('title', $group->exists ? 'Modifier le groupe' : 'Nouveau groupe')
@section('breadcrumb', 'Classes / Groupes')
@section('content')
    <x-page-header :title="$group->exists ? $group->name : 'Nouveau groupe'" :subtitle="$year->name" />
    <form method="POST" action="{{ $group->exists ? route('groups.update', $group) : route('groups.store') }}" class="max-w-xl">
        @csrf
        @if ($group->exists) @method('PUT') @endif
        <x-card class="space-y-4 p-5">
            <x-field name="name" label="Nom" :value="old('name', $group->name)" required />
            <x-field name="code" label="Code" :value="old('code', $group->code)" required />
            <x-select name="level_id" label="Niveau" :value="old('level_id', $group->level_id)" :options="$levels->mapWithKeys(fn ($level) => [$level->id => $level->name])->all()" />
            <x-field name="capacity" type="number" label="Capacité" :value="old('capacity', $group->capacity)" />
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

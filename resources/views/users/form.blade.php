@extends('layouts.app')
@section('title', $user->exists ? 'Modifier le compte' : 'Nouveau compte')
@section('breadcrumb', 'Administration')
@section('content')
    <x-page-header :title="$user->exists ? $user->name : 'Nouveau compte'" />
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="max-w-xl">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <x-card class="space-y-4 p-5">
            <x-field name="name" label="Nom" :value="old('name', $user->name)" required />
            <x-field name="email" type="email" label="E-mail" :value="old('email', $user->email)" required />
            <x-field name="password" type="password" label="{{ $user->exists ? 'Nouveau mot de passe' : 'Mot de passe' }}" />
            <x-select name="role_id" label="Rôle" :value="old('role_id', $user->roles->first()?->id)" :options="$roles->mapWithKeys(fn ($role) => [$role->id => $role->name])->all()" placeholder="Rôle" />
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

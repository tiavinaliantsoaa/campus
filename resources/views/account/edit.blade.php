@extends('layouts.app')
@section('title', 'Mon compte')
@section('breadcrumb', 'Compte')
@section('content')
    <x-page-header title="Mon compte" :subtitle="$user->email.' · '.$user->roleLabel()" />
    <form method="POST" action="{{ route('account.update') }}" class="max-w-xl">
        @csrf
        @method('PUT')
        <x-card class="space-y-4 p-5">
            <x-field name="current_password" type="password" label="Mot de passe actuel" required />
            <x-field name="password" type="password" label="Nouveau mot de passe" required />
            <x-field name="password_confirmation" type="password" label="Confirmation" required />
            <x-button>Mettre à jour</x-button>
        </x-card>
    </form>
@endsection

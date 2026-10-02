@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
    <p class="text-xs font-semibold tracking-[0.16em] text-campus uppercase">{{ config('campus.short_name') }} Campus</p>
    <h1 class="mt-3 text-3xl font-semibold tracking-tight">Connexion</h1>
    <p class="mt-2 text-sm text-ink/60">Accédez à l'espace correspondant à votre rôle.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
        @csrf
        <x-field name="email" type="email" label="E-mail" :value="old('email')" required autofocus />
        <x-field name="password" type="password" label="Mot de passe" required />
        <label class="flex items-center gap-2 text-sm text-ink/70">
            <input type="checkbox" name="remember" value="1" class="rounded border-line text-campus focus:ring-campus" @checked(old('remember'))>
            Se souvenir de moi
        </label>
        <x-button class="w-full">Entrer</x-button>
    </form>

    @if (app()->environment('local'))
        <x-alert tone="info" class="mt-6">
            Environnement local. Compte direction : {{ config('campus.admin_email') }} — mot de passe initial <span class="font-medium">password</span> après le chargement des données de démonstration.
        </x-alert>
    @endif
@endsection

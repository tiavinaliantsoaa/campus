@extends('layouts.app')
@section('title', 'Comptes')
@section('breadcrumb', 'Administration')
@section('content')
    <x-page-header title="Comptes">
        <x-button :href="route('users.create')">Nouveau compte</x-button>
    </x-page-header>
    <x-card class="divide-y divide-line">
        @foreach ($users as $user)
            <a href="{{ route('users.edit', $user) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-canvas">
                <span>{{ $user->name }}<span class="block text-ink/45">{{ $user->email }}</span></span>
                <span>{{ $user->roles->pluck('name')->join(', ') }}</span>
            </a>
        @endforeach
        {{ $users->links() }}
    </x-card>
@endsection

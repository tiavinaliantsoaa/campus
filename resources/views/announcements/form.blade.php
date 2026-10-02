@extends('layouts.app')
@section('title', $announcement->exists ? 'Modifier l\'annonce' : 'Nouvelle annonce')
@section('breadcrumb', 'Communication')
@section('content')
    <x-page-header :title="$announcement->exists ? 'Modifier l\'annonce' : 'Nouvelle annonce'" />
    <form method="POST" action="{{ $announcement->exists ? route('announcements.update', $announcement) : route('announcements.store') }}" class="max-w-3xl">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif
        <x-card class="space-y-4 p-5">
            <x-field name="title" label="Titre" :value="old('title', $announcement->title)" required />
            <x-textarea name="body" label="Message" :value="old('body', $announcement->body)" required />
            <x-select name="audience" label="Destinataires" :value="old('audience', $announcement->audience?->value ?? 'everyone')" :options="collect($audiences)->mapWithKeys(fn ($audience) => [$audience->value => $audience->label()])->all()" placeholder="Audience" />
            <x-select name="role_id" label="Rôle" :value="old('role_id', $announcement->targets->firstWhere('target_type', 'role')?->target_id)" :options="$roles->mapWithKeys(fn ($role) => [$role->id => $role->name])->all()" />
            <x-select name="student_group_id" label="Groupe" :value="old('student_group_id', $announcement->targets->firstWhere('target_type', 'group')?->target_id)" :options="$groups->mapWithKeys(fn ($group) => [$group->id => $group->name])->all()" />
            <x-field name="published_at" type="datetime-local" label="Publication" :value="old('published_at', $announcement->published_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'))" />
            <x-field name="expires_at" type="datetime-local" label="Expiration" :value="old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i'))" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="publish" value="1" @checked(old('publish', $announcement->published_at !== null))> Publier</label>
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Groupes')
@section('breadcrumb', 'Classes / Groupes')
@section('content')
    <x-page-header title="Classes et groupes" :subtitle="$year->name">
        @if (auth()->user()->hasPermission('groups.manage'))
            <x-button :href="route('groups.create')">Nouveau groupe</x-button>
        @endif
        <x-button variant="secondary" :href="route('levels.index')">Niveaux</x-button>
        <x-button variant="secondary" :href="route('subjects.index')">Matières</x-button>
        @if (auth()->user()->hasPermission('timetable.view'))
            <x-button variant="secondary" :href="route('rooms.index')">Salles</x-button>
        @endif
    </x-page-header>
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-ink/45 uppercase"><tr><th class="px-4 py-3">Groupe</th><th class="px-4 py-3">Niveau</th><th class="px-4 py-3">Effectif</th><th class="px-4 py-3">Capacité</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($groups as $group)
                        <tr>
                            <td class="px-4 py-3"><a class="font-medium" href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><p class="text-ink/45">{{ $group->code }}</p></td>
                            <td class="px-4 py-3">{{ $group->level->name }}</td>
                            <td class="px-4 py-3">{{ $group->enrollments_count }}</td>
                            <td class="px-4 py-3">{{ $group->capacity ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-ink/50">Aucun groupe pour cette année.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $groups->links() }}
    </x-card>
@endsection

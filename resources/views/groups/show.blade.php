@extends('layouts.app')
@section('title', $group->name)
@section('breadcrumb', 'Classes / Groupes')
@section('content')
    <x-page-header :title="$group->name" :subtitle="$group->level->name.' · '.$group->academicYear->name">
        @if (auth()->user()->hasPermission('groups.manage'))
            <x-button variant="secondary" :href="route('groups.edit', $group)">Modifier</x-button>
            <x-button variant="danger" type="button" data-confirm data-action="{{ route('groups.destroy', $group) }}" data-method="DELETE" data-message="Supprimer ce groupe ?">Supprimer</x-button>
        @endif
    </x-page-header>
    <section class="grid gap-4 lg:grid-cols-2">
        <x-card>
            <div class="border-b border-line px-5 py-4 font-semibold">Étudiants</div>
            <div class="divide-y divide-line">
                @forelse ($enrollments as $enrollment)
                    <a href="{{ route('students.show', $enrollment->student) }}" class="block px-5 py-3 text-sm hover:bg-canvas">{{ $enrollment->student->full_name }} <span class="text-ink/45">{{ $enrollment->student->matricule }}</span></a>
                @empty
                    <p class="px-5 py-8 text-sm text-ink/50">Aucun étudiant inscrit.</p>
                @endforelse
            </div>
            {{ $enrollments->links() }}
        </x-card>
        <div class="space-y-4">
            <x-card class="p-5">
                <h2 class="font-semibold">Enseignants</h2>
                @forelse ($group->assignments as $assignment)
                    <p class="mt-2 text-sm">{{ $assignment->teacher->full_name }} · {{ $assignment->subject->name }}</p>
                @empty
                    <p class="mt-2 text-sm text-ink/50">Aucune affectation.</p>
                @endforelse
            </x-card>
            @if ($ranking->isNotEmpty())
                <x-card class="p-5">
                    <h2 class="font-semibold">Classement</h2>
                    @foreach ($ranking as $row)
                        <p class="mt-2 text-sm">{{ $row['rank'] ? $row['rank'].'.' : '—' }} {{ $row['student']->full_name }} · {{ $row['average'] ?? 'Sans moyenne' }}</p>
                    @endforeach
                </x-card>
            @endif
        </div>
    </section>
@endsection

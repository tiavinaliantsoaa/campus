@extends('layouts.app')

@section('title', 'Étudiants')
@section('breadcrumb', 'Étudiants')

@section('content')
    <x-page-header eyebrow="Scolarité" title="Étudiants" :subtitle="$year->name">
        @can('create', App\Models\Student::class)
            <x-button :href="route('students.create')">Inscrire un étudiant</x-button>
        @endcan
    </x-page-header>

    <x-card>
        <form method="GET" class="grid gap-3 border-b border-line p-4 md:grid-cols-4">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, matricule, e-mail" class="rounded-lg border border-line px-3 py-2.5 text-sm md:col-span-2">
            <select name="group" class="rounded-lg border border-line px-3 py-2.5 text-sm">
                <option value="">Tous les groupes</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected((string) request('group') === (string) $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-line px-3 py-2.5 text-sm">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <div class="md:col-span-4"><x-button type="submit" variant="secondary">Filtrer</x-button></div>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs tracking-wide text-ink/45 uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">Étudiant</th>
                        <th class="px-4 py-3 font-medium">Matricule</th>
                        <th class="px-4 py-3 font-medium">Groupe</th>
                        <th class="px-4 py-3 font-medium">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($students as $student)
                        @php $enrollment = $student->enrollmentFor($year); @endphp
                        <tr class="hover:bg-canvas/70">
                            <td class="px-4 py-3">
                                <a href="{{ route('students.show', $student) }}" class="font-medium">{{ $student->full_name }}</a>
                                <p class="text-ink/45">{{ $student->email }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $student->matricule }}</td>
                            <td class="px-4 py-3">{{ $enrollment?->group?->name ?? '—' }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$student->status->tone()">{{ $student->status->label() }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-ink/50">Aucun étudiant ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </x-card>
@endsection

@extends('layouts.app')
@section('title', 'Enseignants')
@section('breadcrumb', 'Enseignants')
@section('content')
    <x-page-header title="Enseignants" subtitle="Équipe pédagogique">
        @can('create', App\Models\Teacher::class)
            <x-button :href="route('teachers.create')">Ajouter un enseignant</x-button>
        @endcan
    </x-page-header>
    <x-card>
        <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou matricule" class="flex-1 rounded-lg border border-line px-3 py-2.5 text-sm">
            <select name="status" class="rounded-lg border border-line px-3 py-2.5 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filtrer</x-button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-ink/45 uppercase"><tr><th class="px-4 py-3">Enseignant</th><th class="px-4 py-3">Matricule</th><th class="px-4 py-3">Matières</th><th class="px-4 py-3">Statut</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td class="px-4 py-3"><a class="font-medium" href="{{ route('teachers.show', $teacher) }}">{{ $teacher->full_name }}</a><p class="text-ink/45">{{ $teacher->email }}</p></td>
                            <td class="px-4 py-3">{{ $teacher->employee_number }}</td>
                            <td class="px-4 py-3">{{ $teacher->subjects->pluck('code')->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $teacher->status->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-ink/50">Aucun enseignant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $teachers->links() }}
    </x-card>
@endsection

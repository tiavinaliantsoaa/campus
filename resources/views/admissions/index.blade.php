@extends('layouts.app')
@section('title', 'Admissions')
@section('breadcrumb', 'Admissions')
@section('content')
    <x-page-header title="Admissions" :subtitle="$year->name">
        @can('create', App\Models\Applicant::class)
            <x-button :href="route('applicants.create')">Nouvelle candidature</x-button>
        @endcan
    </x-page-header>
    <x-card>
        <form method="GET" class="grid gap-3 border-b border-line p-4 md:grid-cols-3">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou e-mail" class="rounded-lg border border-line px-3 py-2 text-sm">
            <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm"><option value="">Statut</option>@foreach ($statuses as $status)<option value="{{ $status->id }}" @selected((string) request('status') === (string) $status->id)>{{ $status->name }}</option>@endforeach</select>
            <x-button type="submit" variant="secondary">Filtrer</x-button>
        </form>
        <div class="divide-y divide-line">
            @forelse ($applicants as $applicant)
                <a href="{{ route('applicants.show', $applicant) }}" class="flex items-center justify-between px-5 py-4 text-sm hover:bg-canvas">
                    <span><span class="font-medium">{{ $applicant->full_name }}</span><span class="block text-ink/50">{{ $applicant->email }} · {{ $applicant->level?->name ?? 'Niveau non précisé' }}</span></span>
                    <x-badge>{{ $applicant->admissionStatus->name }}</x-badge>
                </a>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucune candidature.</p>
            @endforelse
        </div>
        {{ $applicants->links() }}
    </x-card>
@endsection

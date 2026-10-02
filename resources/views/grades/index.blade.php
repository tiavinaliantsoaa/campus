@extends('layouts.app')
@section('title', 'Notes et résultats')
@section('breadcrumb', 'Notes & résultats')
@section('content')
    <x-page-header title="Notes et résultats" :subtitle="$year->name">
        @if ($canEnter)
            <x-button :href="route('assessments.create')">Nouvelle évaluation</x-button>
        @endif
    </x-page-header>
    <x-card>
        <form method="GET" class="grid gap-3 border-b border-line p-4 md:grid-cols-3">
            <select name="group" class="rounded-lg border border-line px-3 py-2 text-sm"><option value="">Groupe</option>@foreach ($groups as $group)<option value="{{ $group->id }}" @selected((string) request('group') === (string) $group->id)>{{ $group->name }}</option>@endforeach</select>
            <select name="subject" class="rounded-lg border border-line px-3 py-2 text-sm"><option value="">Matière</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected((string) request('subject') === (string) $subject->id)>{{ $subject->name }}</option>@endforeach</select>
            <x-button type="submit" variant="secondary">Filtrer</x-button>
        </form>
        <div class="divide-y divide-line">
            @forelse ($assessments as $assessment)
                <a href="{{ route('assessments.entry', $assessment) }}" class="flex items-center justify-between px-5 py-4 text-sm hover:bg-canvas">
                    <span>
                        <span class="block font-medium">{{ $assessment->title }}</span>
                        <span class="text-ink/55">{{ $assessment->subject->name }} · {{ $assessment->group->name }} · {{ $assessment->assessed_on->translatedFormat('j M Y') }}</span>
                    </span>
                    <x-badge :tone="$assessment->status->tone()">{{ $assessment->status->label() }}</x-badge>
                </a>
            @empty
                <p class="px-5 py-10 text-sm text-ink/50">Aucune évaluation.</p>
            @endforelse
        </div>
        {{ $assessments->links() }}
    </x-card>
@endsection

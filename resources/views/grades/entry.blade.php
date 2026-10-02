@extends('layouts.app')
@section('title', $assessment->title)
@section('breadcrumb', 'Notes & résultats')
@section('content')
    <x-page-header :title="$assessment->title" :subtitle="$assessment->subject->name.' · '.$assessment->group->name.' · coeff. '.$assessment->coefficient">
        <x-badge :tone="$assessment->status->tone()">{{ $assessment->status->label() }}</x-badge>
    </x-page-header>
    <form method="POST" action="{{ route('assessments.save', $assessment) }}">
        @csrf
        <x-card class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-ink/45 uppercase"><tr><th class="px-4 py-3">Étudiant</th><th class="px-4 py-3">Note / {{ $assessment->max_score }}</th><th class="px-4 py-3">Commentaire</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($enrollments as $enrollment)
                        @php $grade = $grades->get($enrollment->student_id); @endphp
                        <tr>
                            <td class="px-4 py-3">{{ $enrollment->student->full_name }}</td>
                            <td class="px-4 py-3"><input type="number" step="0.01" min="0" max="{{ $assessment->max_score }}" name="scores[{{ $enrollment->student_id }}]" value="{{ old('scores.'.$enrollment->student_id, $grade?->score) }}" class="w-28 rounded-lg border border-line px-3 py-2" @disabled(! $canEnter || $assessment->isLocked())></td>
                            <td class="px-4 py-3"><input name="comments[{{ $enrollment->student_id }}]" value="{{ old('comments.'.$enrollment->student_id, $grade?->comment) }}" class="w-full rounded-lg border border-line px-3 py-2" @disabled(! $canEnter || $assessment->isLocked())></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
        @if ($canEnter && ! $assessment->isLocked())
            <div class="mt-4 flex flex-wrap gap-2">
                <x-button>Enregistrer les notes</x-button>
            </div>
        @endif
    </form>
    <div class="flex flex-wrap gap-2">
        @if ($canEnter && $assessment->status->value === 'draft')
            <form method="POST" action="{{ route('assessments.submit', $assessment) }}">@csrf<x-button variant="secondary">Soumettre pour validation</x-button></form>
        @endif
        @if ($canValidate && $assessment->status->value !== 'validated')
            <form method="POST" action="{{ route('assessments.validate', $assessment) }}">@csrf<x-button>Valider les notes</x-button></form>
        @endif
        @if ($canValidate && $assessment->isLocked())
            <form method="POST" action="{{ route('assessments.reopen', $assessment) }}">@csrf<x-button variant="secondary">Rouvrir</x-button></form>
        @endif
    </div>
@endsection

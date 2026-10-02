@extends('layouts.app')
@section('title', 'Faire l\'appel')
@section('breadcrumb', 'Assiduité')
@section('content')
    <x-page-header title="Faire l'appel" :subtitle="$course->displayTitle().' · '.$course->starts_at->translatedFormat('l j F Y H:i').' · '.$course->group->name" />
    <form method="POST" action="{{ route('attendance.store', $course) }}">
        @csrf
        <x-card class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-ink/45 uppercase"><tr><th class="px-4 py-3">Étudiant</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Commentaire</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($enrollments as $enrollment)
                        @php $current = $existing->get($enrollment->student_id); @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $enrollment->student->full_name }}</td>
                            <td class="px-4 py-3">
                                <select name="statuses[{{ $enrollment->student_id }}]" class="rounded-lg border border-line px-3 py-2">
                                    @foreach (App\Enums\AttendanceStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(old('statuses.'.$enrollment->student_id, $current?->status?->value ?? 'present') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3"><input name="comments[{{ $enrollment->student_id }}]" value="{{ old('comments.'.$enrollment->student_id, $current?->comment) }}" class="w-full rounded-lg border border-line px-3 py-2"></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-ink/50">Aucun étudiant actif dans ce groupe.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
        @if ($enrollments->isNotEmpty())
            <div class="mt-4"><x-button>Enregistrer l'appel</x-button></div>
        @endif
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Assiduité')
@section('breadcrumb', 'Assiduité')
@section('content')
    <x-page-header title="Assiduité" :subtitle="$year->name" />
    <x-card class="p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-4">
            <select name="group" class="rounded-lg border border-line px-3 py-2 text-sm"><option value="">Groupe</option>@foreach ($groups as $group)<option value="{{ $group->id }}" @selected((string) request('group') === (string) $group->id)>{{ $group->name }}</option>@endforeach</select>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-line px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-line px-3 py-2 text-sm">
            <x-button type="submit" variant="secondary">Filtrer</x-button>
        </form>
    </x-card>
    <x-card>
        <div class="border-b border-line px-5 py-4 font-semibold">Séances</div>
        <div class="divide-y divide-line">
            @forelse ($courses as $course)
                <div class="flex flex-col gap-2 px-5 py-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium">{{ $course->starts_at->translatedFormat('l j F H:i') }} · {{ $course->displayTitle() }}</p>
                        <p class="text-ink/55">{{ $course->group->name }} · {{ $course->teacher->full_name }} · {{ $course->attendance_records_count }} pointage(s)</p>
                    </div>
                    @can('recordAttendance', $course)
                        <x-button variant="secondary" :href="route('attendance.roll', $course)">Faire l'appel</x-button>
                    @endcan
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucune séance.</p>
            @endforelse
        </div>
        {{ $courses->links() }}
    </x-card>
    <x-card>
        <div class="border-b border-line px-5 py-4 font-semibold">Historique récent</div>
        @forelse ($history as $record)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <span>{{ $record->student->full_name }} · {{ $record->course->subject->name }} · {{ $record->course->starts_at->format('d/m H:i') }}</span>
                <x-badge :tone="$record->status->tone()">{{ $record->status->label() }}</x-badge>
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucun pointage.</p>
        @endforelse
    </x-card>
@endsection

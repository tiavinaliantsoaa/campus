@extends('layouts.app')
@section('title', $teacher->exists ? 'Modifier un enseignant' : 'Nouvel enseignant')
@section('breadcrumb', 'Enseignants')
@section('content')
    <x-page-header :title="$teacher->exists ? $teacher->full_name : 'Nouvel enseignant'" :subtitle="$year->name" />
    <form method="POST" action="{{ $teacher->exists ? route('teachers.update', $teacher) : route('teachers.store') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2">
        @csrf
        @if ($teacher->exists) @method('PUT') @endif
        <x-card class="space-y-4 p-5">
            <x-field name="first_name" label="Prénom" :value="old('first_name', $teacher->first_name)" required />
            <x-field name="last_name" label="Nom" :value="old('last_name', $teacher->last_name)" required />
            <x-field name="email" type="email" label="E-mail" :value="old('email', $teacher->email)" />
            <x-field name="phone" label="Téléphone" :value="old('phone', $teacher->phone)" />
            <x-select name="status" label="Statut" :value="old('status', $teacher->status?->value ?? 'active')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" placeholder="Statut" />
            <x-field name="photo" type="file" label="Photo" accept="image/*" />
            @unless ($teacher->user_id)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="create_account" value="1" @checked(old('create_account'))> Créer un accès</label>
            @endunless
        </x-card>
        <x-card class="space-y-4 p-5">
            <fieldset>
                <legend class="text-sm font-medium">Matières</legend>
                <div class="mt-3 grid gap-2">
                    @foreach ($subjects as $subject)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" @checked(in_array($subject->id, old('subject_ids', $teacher->subjects->pluck('id')->all())))>
                            {{ $subject->name }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <h2 class="font-semibold">Affectations {{ $year->name }}</h2>
            @for ($index = 0; $index < 4; $index++)
                @php $assignment = $teacher->assignments->get($index); @endphp
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-select name="assignments[{{ $index }}][student_group_id]" label="Groupe" :value="old('assignments.'.$index.'.student_group_id', $assignment?->student_group_id)" :options="$groups->mapWithKeys(fn ($group) => [$group->id => $group->name])->all()" />
                    <x-select name="assignments[{{ $index }}][subject_id]" label="Matière" :value="old('assignments.'.$index.'.subject_id', $assignment?->subject_id)" :options="$subjects->mapWithKeys(fn ($subject) => [$subject->id => $subject->name])->all()" />
                </div>
            @endfor
            <x-textarea name="biography" label="Biographie" :value="old('biography', $teacher->biography)" />
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

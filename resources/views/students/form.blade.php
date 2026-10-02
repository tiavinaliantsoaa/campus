@extends('layouts.app')

@section('title', $student->exists ? 'Modifier un étudiant' : 'Inscrire un étudiant')
@section('breadcrumb', 'Étudiants')

@section('content')
    <x-page-header :title="$student->exists ? 'Modifier '.$student->full_name : 'Nouvelle inscription'" :subtitle="$year->name" />

    <form method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2">
        @csrf
        @if ($student->exists)
            @method('PUT')
        @endif

        <x-card class="space-y-4 p-5 lg:col-span-2">
            <h2 class="font-semibold">Identité</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="first_name" label="Prénom" :value="old('first_name', $student->first_name)" required />
                <x-field name="last_name" label="Nom" :value="old('last_name', $student->last_name)" required />
                <x-field name="email" type="email" label="E-mail" :value="old('email', $student->email)" />
                <x-field name="phone" label="Téléphone" :value="old('phone', $student->phone)" />
                <x-field name="birth_date" type="date" label="Date de naissance" :value="old('birth_date', $student->birth_date?->toDateString())" />
                <x-select name="gender" label="Genre" :value="old('gender', $student->gender?->value)" :options="collect(App\Enums\Gender::cases())->mapWithKeys(fn ($gender) => [$gender->value => $gender->label()])->all()" />
                <x-field name="address" label="Adresse" :value="old('address', $student->address)" />
                <x-field name="city" label="Ville" :value="old('city', $student->city)" />
                <x-field name="country" label="Pays" :value="old('country', $student->country ?? 'Madagascar')" />
                <x-field name="photo" type="file" label="Photo" accept="image/*" />
            </div>
        </x-card>

        <x-card class="space-y-4 p-5">
            <h2 class="font-semibold">Scolarité {{ $year->name }}</h2>
            <x-select name="level_id" label="Niveau" :value="old('level_id', $enrollment?->level_id)" :options="$levels->mapWithKeys(fn ($level) => [$level->id => $level->name])->all()" />
            <x-select name="student_group_id" label="Groupe" :value="old('student_group_id', $enrollment?->student_group_id)" :options="$groups->mapWithKeys(fn ($group) => [$group->id => $group->name])->all()" />
            <x-select name="status" label="Statut" :value="old('status', $student->status?->value ?? 'active')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" placeholder="Statut" />
            <x-field name="enrolled_on" type="date" label="Date d'inscription" :value="old('enrolled_on', $enrollment?->enrolled_on?->toDateString() ?? now()->toDateString())" />
            @if ($student->matricule)
                <p class="text-sm text-ink/55">Matricule {{ $student->matricule }}</p>
            @endif
            @unless ($student->user_id)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="create_account" value="1" @checked(old('create_account'))> Créer un accès au portail</label>
            @endunless
        </x-card>

        <x-card class="space-y-4 p-5">
            <h2 class="font-semibold">Responsable légal</h2>
            <x-field name="guardian_first_name" label="Prénom" :value="old('guardian_first_name')" />
            <x-field name="guardian_last_name" label="Nom" :value="old('guardian_last_name')" />
            <x-field name="guardian_email" type="email" label="E-mail" :value="old('guardian_email')" />
            <x-field name="guardian_phone" label="Téléphone" :value="old('guardian_phone')" />
            <x-field name="guardian_relationship" label="Lien" :value="old('guardian_relationship', 'Parent')" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="guardian_create_account" value="1" @checked(old('guardian_create_account'))> Créer un accès parent</label>
        </x-card>

        <x-card class="space-y-4 p-5 lg:col-span-2">
            <h2 class="font-semibold">Contacts d'urgence</h2>
            @for ($index = 0; $index < 2; $index++)
                @php $contact = $student->emergencyContacts->get($index); @endphp
                <div class="grid gap-3 sm:grid-cols-4">
                    <x-field name="emergency_contacts[{{ $index }}][name]" label="Nom" :value="old('emergency_contacts.'.$index.'.name', $contact?->name)" />
                    <x-field name="emergency_contacts[{{ $index }}][relationship]" label="Lien" :value="old('emergency_contacts.'.$index.'.relationship', $contact?->relationship)" />
                    <x-field name="emergency_contacts[{{ $index }}][phone]" label="Téléphone" :value="old('emergency_contacts.'.$index.'.phone', $contact?->phone)" />
                    <x-field name="emergency_contacts[{{ $index }}][email]" label="E-mail" :value="old('emergency_contacts.'.$index.'.email', $contact?->email)" />
                </div>
            @endfor
            <x-textarea name="notes" label="Notes internes" :value="old('notes', $student->notes)" />
            <x-button>Enregistrer</x-button>
        </x-card>
    </form>
@endsection

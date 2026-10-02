@extends('layouts.app')
@section('title', 'Nouvelle échéance')
@section('breadcrumb', 'Scolarité & paiements')
@section('content')
    <x-page-header title="Nouvelle échéance" :subtitle="$year->name" />
    <form method="POST" action="{{ route('finance.installments.store') }}" class="max-w-xl">
        @csrf
        <x-card class="space-y-4 p-5">
            <x-select name="student_id" label="Étudiant" :value="old('student_id', request('student'))" :options="$students->mapWithKeys(fn ($student) => [$student->id => $student->full_name.' · '.$student->matricule])->all()" />
            <x-select name="fee_tariff_id" label="Tarif lié" :value="old('fee_tariff_id')" :options="$tariffs->mapWithKeys(fn ($tariff) => [$tariff->id => $tariff->name])->all()" />
            <x-field name="label" label="Libellé" :value="old('label')" required />
            <x-field name="amount_due" type="number" step="0.01" label="Montant dû" :value="old('amount_due')" required />
            <x-field name="discount" type="number" step="0.01" label="Remise" :value="old('discount', 0)" />
            <x-field name="due_on" type="date" label="Date d'échéance" :value="old('due_on')" required />
            <x-button>Créer l'échéance</x-button>
        </x-card>
    </form>
@endsection

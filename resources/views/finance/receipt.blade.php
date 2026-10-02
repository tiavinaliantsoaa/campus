@extends('layouts.app')
@section('title', $payment->receipt_number)
@section('breadcrumb', 'Reçu')
@section('content')
    <x-card class="mx-auto max-w-xl p-8" id="receipt">
        <p class="text-xs tracking-[0.16em] text-campus uppercase">{{ config('campus.short_name') }}</p>
        <h1 class="mt-2 text-2xl font-semibold">Reçu {{ $payment->receipt_number }}</h1>
        <dl class="mt-6 space-y-2 text-sm">
            <div class="flex justify-between"><dt>Étudiant</dt><dd>{{ $payment->student->full_name }}</dd></div>
            <div class="flex justify-between"><dt>Échéance</dt><dd>{{ $payment->installment->label }}</dd></div>
            <div class="flex justify-between"><dt>Montant</dt><dd class="font-semibold">{{ \App\Support\Money::format($payment->amount) }}</dd></div>
            <div class="flex justify-between"><dt>Date</dt><dd>{{ $payment->paid_on->translatedFormat('j F Y') }}</dd></div>
            <div class="flex justify-between"><dt>Mode</dt><dd>{{ $payment->method->label() }}</dd></div>
            <div class="flex justify-between"><dt>Référence</dt><dd>{{ $payment->reference ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt>Encaissé par</dt><dd>{{ $payment->receiver?->name ?? '—' }}</dd></div>
        </dl>
    </x-card>
    <div class="mt-4 flex justify-center gap-2 print:hidden">
        <x-button type="button" onclick="window.print()">Imprimer</x-button>
        @can('delete', $payment)
            <x-button variant="danger" type="button" data-confirm data-action="{{ route('finance.payments.destroy', $payment) }}" data-method="DELETE" data-message="Annuler ce paiement ?">Annuler le paiement</x-button>
        @endcan
    </div>
@endsection

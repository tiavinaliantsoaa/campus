@extends('layouts.app')
@section('title', 'Relevé de '.$student->full_name)
@section('breadcrumb', 'Scolarité & paiements')
@section('content')
    <x-page-header :title="$student->full_name" :subtitle="'Attendu '.\App\Support\Money::format($balance['expected']).' · Payé '.\App\Support\Money::format($balance['paid']).' · Reste '.\App\Support\Money::format($balance['remaining'])" />
    <x-card class="divide-y divide-line">
        @forelse ($installments as $installment)
            <div class="px-5 py-4 text-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $installment->label }}</p>
                        <p class="text-ink/55">Dû {{ \App\Support\Money::format($finance->expected($installment)) }} · Payé {{ \App\Support\Money::format($finance->paid($installment)) }} · Reste {{ \App\Support\Money::format($finance->remaining($installment)) }}</p>
                    </div>
                    <x-badge :tone="$finance->status($installment) === 'paid' || $finance->status($installment) === 'overpaid' ? 'success' : 'warning'">{{ $finance->statusLabel($finance->status($installment)) }}</x-badge>
                </div>
                @if ($canRecord)
                    <form method="POST" action="{{ route('finance.payments.store') }}" class="mt-3 grid gap-2 md:grid-cols-5">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                        <input type="hidden" name="fee_installment_id" value="{{ $installment->id }}">
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Montant" class="rounded-lg border border-line px-3 py-2" required>
                        <input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="rounded-lg border border-line px-3 py-2" required>
                        <select name="method" class="rounded-lg border border-line px-3 py-2">
                            @foreach ($methods as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach
                        </select>
                        <input name="reference" placeholder="Référence" class="rounded-lg border border-line px-3 py-2">
                        <x-button type="submit">Encaisser</x-button>
                    </form>
                @endif
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucune échéance sur cette année.</p>
        @endforelse
    </x-card>
    <x-card>
        <div class="border-b border-line px-5 py-4 font-semibold">Historique des paiements</div>
        @forelse ($payments as $payment)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <a href="{{ route('finance.receipt', $payment) }}" class="text-campus">{{ $payment->receipt_number }}</a>
                <span>{{ \App\Support\Money::format($payment->amount) }} · {{ $payment->paid_on->translatedFormat('j M Y') }} · {{ $payment->method->label() }}</span>
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucun paiement.</p>
        @endforelse
    </x-card>
@endsection

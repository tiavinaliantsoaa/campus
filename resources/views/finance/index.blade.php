@extends('layouts.app')
@section('title', 'Scolarité et paiements')
@section('breadcrumb', 'Scolarité & paiements')
@section('content')
    <x-page-header title="Scolarité et paiements" :subtitle="$year->name">
        @if ($manage)
            <x-button :href="route('finance.installments.create')">Nouvelle échéance</x-button>
        @endif
    </x-page-header>
    @if ($manage)
        <form method="POST" action="{{ route('finance.tariffs.store') }}" class="grid gap-3 rounded-2xl border border-line bg-white p-4 md:grid-cols-3">
            @csrf
            <x-field name="name" label="Tarif" :value="old('name')" required />
            <x-field name="amount" type="number" step="0.01" label="Montant" :value="old('amount')" required />
            <x-field name="due_on" type="date" label="Échéance" :value="old('due_on')" />
            <div class="md:col-span-3"><x-button>Enregistrer le tarif</x-button></div>
        </form>
        <x-card class="divide-y divide-line">
            @forelse ($tariffs as $tariff)
                <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <span>{{ $tariff->name }} · {{ \App\Support\Money::format($tariff->amount) }} · {{ $tariff->group?->name ?? $tariff->level?->name ?? 'Tout le campus' }}</span>
                    <form method="POST" action="{{ route('finance.tariffs.assign', $tariff) }}">@csrf<x-button variant="secondary" type="submit">Générer les échéances</x-button></form>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-ink/50">Aucun tarif.</p>
            @endforelse
        </x-card>
    @endif
    <x-card>
        <form method="GET" class="border-b border-line p-4"><input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un étudiant" class="w-full rounded-lg border border-line px-3 py-2 text-sm"></form>
        <div class="divide-y divide-line">
            @forelse ($installments as $installment)
                <a href="{{ route('finance.statement', $installment->student) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-canvas">
                    <span>{{ $installment->student->full_name }} · {{ $installment->label }}<span class="block text-ink/45">{{ $installment->due_on->translatedFormat('j M Y') }}</span></span>
                    <x-badge :tone="$finance->status($installment) === 'paid' ? 'success' : ($finance->status($installment) === 'unpaid' ? 'danger' : 'warning')">{{ $finance->statusLabel($finance->status($installment)) }}</x-badge>
                </a>
            @empty
                <p class="px-5 py-8 text-sm text-ink/50">Aucune échéance.</p>
            @endforelse
        </div>
        {{ $installments->links() }}
    </x-card>
@endsection

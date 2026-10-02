@extends('layouts.app')
@section('title', 'Impayés')
@section('breadcrumb', 'Rapports')
@section('content')
    <x-page-header title="Échéances ouvertes" :subtitle="$enrolled.' inscrits · '.$year->name" />
    <x-card class="divide-y divide-line">
        @forelse ($installments as $installment)
            <a href="{{ route('finance.statement', $installment->student) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-canvas">
                <span>{{ $installment->student->full_name }} · {{ $installment->label }}</span>
                <span>{{ \App\Support\Money::format($finance->remaining($installment)) }}</span>
            </a>
        @empty
            <p class="px-5 py-8 text-sm text-ink/50">Aucun impayé.</p>
        @endforelse
    </x-card>
@endsection

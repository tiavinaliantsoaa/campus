@extends('layouts.app')
@section('title', 'Rapports')
@section('breadcrumb', 'Rapports')
@section('content')
    <x-page-header title="Rapports" :subtitle="$year->name" />
    <section class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('reports.attendance') }}" class="rounded-2xl border border-line bg-white p-5 hover:border-campus"><h2 class="font-semibold">Présence</h2><p class="mt-2 text-sm text-ink/55">Taux par groupe.</p></a>
        <a href="{{ route('reports.grades') }}" class="rounded-2xl border border-line bg-white p-5 hover:border-campus"><h2 class="font-semibold">Résultats</h2><p class="mt-2 text-sm text-ink/55">Moyennes validées.</p></a>
        @if ($canFinance)
            <a href="{{ route('reports.finance') }}" class="rounded-2xl border border-line bg-white p-5 hover:border-campus"><h2 class="font-semibold">Impayés</h2><p class="mt-2 text-sm text-ink/55">Échéances encore ouvertes.</p></a>
        @endif
    </section>
@endsection

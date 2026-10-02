@extends('layouts.app')
@section('title', 'Rapport de résultats')
@section('breadcrumb', 'Rapports')
@section('content')
    <x-page-header title="Résultats par groupe" :subtitle="$year->name" />
    <x-card class="divide-y divide-line">
        @foreach ($rows as $row)
            <div class="flex items-center justify-between px-5 py-3 text-sm"><span>{{ $row['group']->name }}</span><span>{{ $row['average'] === null ? '—' : $row['average'].'/20' }} · {{ $row['count'] }} moyenne(s)</span></div>
        @endforeach
    </x-card>
@endsection

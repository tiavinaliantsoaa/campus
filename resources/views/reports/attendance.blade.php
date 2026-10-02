@extends('layouts.app')
@section('title', 'Rapport de présence')
@section('breadcrumb', 'Rapports')
@section('content')
    <x-page-header title="Présence par groupe" :subtitle="$year->name" />
    <x-card class="divide-y divide-line">
        @foreach ($rows as $row)
            <div class="flex items-center justify-between px-5 py-3 text-sm"><span>{{ $row['group']->name }}</span><span>{{ $row['rate'] === null ? '—' : $row['rate'].' %' }} · {{ $row['total'] }} pointages</span></div>
        @endforeach
    </x-card>
@endsection

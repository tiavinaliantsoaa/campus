<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <title>@yield('title', 'Campus') — {{ config('campus.short_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas font-sans text-ink antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
        @include('layouts.sidebar')
        <div class="min-w-0">
            @include('layouts.header')
            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    <x-flash />
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <dialog data-confirm-dialog class="w-[min(100%,28rem)] rounded-2xl border border-line p-0 backdrop:bg-ink/40">
        <form method="POST" class="space-y-5 p-6">
            @csrf
            <input type="hidden" name="_method" value="DELETE" data-confirm-method>
            <div>
                <h2 class="text-lg font-semibold">Confirmer</h2>
                <p class="mt-2 text-sm text-ink/70" data-confirm-message>Confirmer cette action ?</p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" data-confirm-cancel class="rounded-lg border border-line px-4 py-2 text-sm">Annuler</button>
                <button type="submit" class="rounded-lg bg-campus px-4 py-2 text-sm font-medium text-white">Confirmer</button>
            </div>
        </form>
    </dialog>
</body>
</html>

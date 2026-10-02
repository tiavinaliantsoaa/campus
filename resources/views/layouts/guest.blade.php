<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <title>@yield('title', 'Connexion') — {{ config('campus.short_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden flex-col justify-between bg-sidebar p-12 text-white lg:flex">
            <div>
                <p class="text-3xl font-semibold">{{ config('campus.short_name') }}</p>
                <p class="mt-2 text-xs tracking-[0.18em] text-white/45 uppercase">Business School</p>
            </div>
            <div>
                <p class="text-4xl font-semibold tracking-tight">Une vision claire de votre campus.</p>
                <p class="mt-4 max-w-md text-white/65">{{ config('campus.tagline') }} {{ config('campus.city') }}, {{ config('campus.country') }}.</p>
            </div>
        </section>
        <main class="flex items-center justify-center px-6 py-16">
            <div class="w-full max-w-md">@yield('content')</div>
        </main>
    </div>
</body>
</html>

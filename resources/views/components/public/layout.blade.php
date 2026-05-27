<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Budżet Obywatelski' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-serif-display:400|manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="@if(request()->routeIs('public.home')) page-home @else page-public @endif">
<a class="skip-link" href="#content">Przejdź do treści</a>
<header class="site-header">
    <div class="site-ribbon">
        <div class="site-ribbon-inner">
            <p>Portal mieszkańca dla budżetu obywatelskiego</p>
            <p>Tu jest miejsce na logotyp miasta, dane edycji i krótki komunikat urzędu</p>
        </div>
    </div>
    <nav class="site-nav" aria-label="Główna nawigacja">
        <a class="brand" href="{{ route('public.home') }}">
            <span class="brand-mark" aria-hidden="true">
                <span>M</span>
            </span>
            <span class="brand-copy">
                <strong>Budżet Obywatelski</strong>
                <span class="brand-subtitle">Miejsce na logotyp miasta i nazwę miasta</span>
            </span>
        </a>
        <div class="nav-shell">
            <div class="nav-links">
                <a class="nav-link" href="{{ route('public.home') }}" @if(request()->routeIs('public.home')) aria-current="page" @endif>Start</a>
                <a class="nav-link" href="{{ route('public.projects.index') }}" @if(request()->routeIs('public.projects.index') || request()->routeIs('public.projects.show')) aria-current="page" @endif>Projekty</a>
                <a class="nav-link" href="{{ route('public.projects.map') }}" @if(request()->routeIs('public.projects.map')) aria-current="page" @endif>Mapa</a>
                <a class="nav-link" href="{{ route('public.voting.welcome') }}" @if(request()->routeIs('public.voting.*')) aria-current="page" @endif>Głosowanie</a>
                <a class="nav-link" href="{{ route('public.results.index') }}" @if(request()->routeIs('public.results.*')) aria-current="page" @endif>Wyniki</a>
                <a class="nav-link" href="{{ route('public.announcements.index') }}" @if(request()->routeIs('public.announcements.*')) aria-current="page" @endif>Ogłoszenia</a>
            </div>
            <div class="nav-actions">
                <a class="nav-link login" href="/admin">Logowanie</a>
                <a class="button nav-button" href="{{ route('public.projects.create') }}">Zgłoś projekt</a>
            </div>
        </div>
    </nav>
</header>
<main id="content">
    @if (session('status'))
        <p class="notice site-notice">{{ session('status') }}</p>
    @endif

    {{ $slot }}
</main>
<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <p class="footer-title">Budżet Obywatelski</p>
            <p>Publiczny portal dla mieszkanek i mieszkańców. Tu można zgłosić projekt, śledzić harmonogram, przeglądać propozycje i sprawdzać wyniki głosowania.</p>
        </div>
        <div>
            <p class="footer-title">Kontakt</p>
            <p>Urząd miasta<br>Biuro dialogu i partycypacji<br><a href="mailto:kontakt@example.test">kontakt@example.test</a></p>
        </div>
        <div>
            <p class="footer-title">Na skróty</p>
            <p><a href="{{ route('public.info.show', 'o-budzecie') }}">O budżecie</a></p>
            <p><a href="{{ route('public.info.show', 'harmonogram') }}">Harmonogram</a></p>
            <p><a href="{{ route('public.announcements.index') }}">Ogłoszenia</a></p>
        </div>
    </div>
</footer>
@livewireScripts
</body>
</html>

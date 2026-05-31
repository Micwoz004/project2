<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $spaState['app']['title'] ?? 'Platforma mieszkańca' }}</title>
    @vite(['resources/css/public-spa.css', 'resources/js/public-spa.js'])
</head>
<body>
    <div id="bo-spa-root"></div>
    @include('all-in-one-accessibility::frontend-widget')
    <noscript>
        <main>
            <h1>Budżet Obywatelski Miasta</h1>
            <p>Projekty, ogłoszenia, harmonogram i formularz zgłoszenia projektu są dostępne w publicznej aplikacji mieszkańca.</p>
            @foreach (($spaState['announcements'] ?? []) as $announcement)
                <article>
                    <h2>{{ $announcement['title'] }}</h2>
                    <p>{{ $announcement['lead'] }}</p>
                </article>
            @endforeach
            @foreach (($spaState['pages'] ?? []) as $page)
                <article>
                    <h2>{{ $page['title'] }}</h2>
                </article>
            @endforeach
            @foreach (($spaState['legacyText'] ?? []) as $text)
                <p>{{ $text }}</p>
            @endforeach
            @if (($spaState['app']['currentPath'] ?? '') === '/wyniki' && isset($spaState['results']['message']))
                <p>{{ $spaState['results']['message'] }}</p>
            @endif
        </main>
    </noscript>
    <script>
        window.BO_SPA = @json($spaState);
    </script>
</body>
</html>

<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SBO Szczecin' }}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #17202a; background: #f6f7f9; }
        header { background: #ffffff; border-bottom: 1px solid #d7dde5; }
        nav, main { max-width: 1120px; margin: 0 auto; padding: 18px; }
        nav { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
        nav a { color: #173b68; text-decoration: none; font-weight: 700; }
        h1 { font-size: 30px; margin: 12px 0 18px; }
        h2 { font-size: 22px; margin: 28px 0 12px; }
        .toolbar, .panel { background: #ffffff; border: 1px solid #d7dde5; padding: 16px; border-radius: 6px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
        .item { background: #ffffff; border: 1px solid #d7dde5; border-radius: 6px; padding: 16px; }
        .muted { color: #5f6b7a; }
        label { display: block; font-weight: 700; margin-top: 12px; }
        input, select, textarea { width: 100%; box-sizing: border-box; border: 1px solid #b7c0cc; border-radius: 4px; padding: 10px; font: inherit; background: #ffffff; }
        textarea { min-height: 110px; }
        button, .button { display: inline-block; border: 0; border-radius: 4px; padding: 11px 15px; background: #173b68; color: #ffffff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .error { color: #a51616; margin-top: 6px; }
        .notice { background: #e9f5ee; border: 1px solid #b8d9c6; padding: 12px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; background: #ffffff; }
        th, td { border-bottom: 1px solid #d7dde5; padding: 10px; text-align: left; }
    </style>
</head>
<body>
<header>
    <nav>
        <a href="{{ route('public.projects.index') }}">Projekty</a>
        <a href="{{ route('public.projects.create') }}">Zgłoś projekt</a>
        <a href="{{ route('public.voting.welcome') }}">Głosowanie</a>
        <a href="{{ route('public.results.index') }}">Wyniki</a>
        <a href="{{ route('public.reports.index') }}">Raporty</a>
        <a href="/admin">Admin</a>
    </nav>
</header>
<main>
    @if (session('status'))
        <p class="notice">{{ session('status') }}</p>
    @endif

    {{ $slot }}
</main>
</body>
</html>

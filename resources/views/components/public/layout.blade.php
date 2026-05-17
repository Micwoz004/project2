<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SBO Szczecin' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|source-serif-4:600,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f8f3;
            --bg-strong: #eef3ed;
            --ink: #15231f;
            --ink-soft: #58645f;
            --line: #d9dfd6;
            --surface: #ffffff;
            --surface-soft: #fbfcf8;
            --primary: #0f5a4d;
            --primary-strong: #09483e;
            --primary-soft: #dceee8;
            --accent: #c84f3d;
            --warning: #a3382a;
            --shadow: 0 18px 45px rgba(21, 35, 31, .08);
            --radius: 8px;
            --radius-sm: 5px;
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(15, 90, 77, .08), transparent 30rem),
                linear-gradient(90deg, rgba(21, 35, 31, .035) 1px, transparent 1px),
                linear-gradient(0deg, rgba(21, 35, 31, .028) 1px, transparent 1px),
                var(--bg);
            background-size: auto, 42px 42px, 42px 42px, auto;
            font-family: Manrope, ui-sans-serif, system-ui, sans-serif;
            line-height: 1.55;
        }

        a {
            color: var(--primary);
            text-decoration-thickness: 1px;
            text-underline-offset: 4px;
        }

        a:hover {
            color: var(--primary-strong);
        }

        .skip-link {
            left: 16px;
            position: absolute;
            top: -44px;
            z-index: 50;
            border-radius: var(--radius-sm);
            background: var(--ink);
            color: #fff;
            padding: 10px 14px;
            text-decoration: none;
        }

        .skip-link:focus {
            top: 12px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid rgba(21, 35, 31, .1);
            background: rgba(247, 248, 243, .92);
            backdrop-filter: blur(14px);
        }

        .site-nav,
        main {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .site-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 16px 0;
        }

        .brand {
            display: inline-grid;
            grid-template-columns: 42px auto;
            align-items: center;
            gap: 12px;
            color: var(--ink);
            font-weight: 800;
            letter-spacing: 0;
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 1px solid rgba(15, 90, 77, .2);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: 0 8px 22px rgba(15, 90, 77, .1);
            color: var(--primary);
            font-family: "Source Serif 4", Georgia, serif;
            font-size: 21px;
            font-weight: 700;
        }

        .brand-subtitle {
            display: block;
            color: var(--ink-soft);
            font-size: 12px;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .nav-link {
            border-radius: var(--radius-sm);
            color: var(--ink-soft);
            font-size: 14px;
            font-weight: 800;
            padding: 9px 11px;
            text-decoration: none;
        }

        .nav-link:hover,
        .nav-link[aria-current="page"] {
            background: var(--primary-soft);
            color: var(--primary-strong);
        }

        .nav-link.admin {
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
        }

        main {
            padding: 34px 0 64px;
        }

        .page-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
            gap: 22px;
            margin: 0 0 24px;
            padding: 28px;
            border: 1px solid rgba(21, 35, 31, .1);
            border-radius: var(--radius);
            background:
                linear-gradient(140deg, rgba(255, 255, 255, .96), rgba(251, 252, 248, .88)),
                linear-gradient(90deg, rgba(15, 90, 77, .12), rgba(200, 79, 61, .08));
            box-shadow: var(--shadow);
        }

        .page-title,
        h1 {
            margin: 0;
            color: var(--ink);
            font-family: "Source Serif 4", Georgia, serif;
            font-size: clamp(34px, 5vw, 58px);
            font-weight: 700;
            letter-spacing: 0;
            line-height: .98;
        }

        .page-summary {
            max-width: 760px;
            margin: 14px 0 0;
            color: var(--ink-soft);
            font-size: 17px;
        }

        h2 {
            margin: 28px 0 12px;
            color: var(--ink);
            font-size: 22px;
            line-height: 1.2;
        }

        h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .toolbar,
        .panel,
        .item,
        .notice,
        .empty-state {
            border: 1px solid rgba(21, 35, 31, .1);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: 0 12px 34px rgba(21, 35, 31, .06);
        }

        .toolbar,
        .panel,
        .notice,
        .empty-state {
            padding: 20px;
        }

        .toolbar {
            display: grid;
            grid-template-columns: repeat(4, minmax(160px, 1fr)) auto;
            align-items: end;
            gap: 14px;
            margin-bottom: 24px;
        }

        .form-panel {
            display: grid;
            gap: 12px;
            max-width: 920px;
        }

        .form-panel h2 {
            margin-top: 30px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
        }

        .form-panel h2:first-of-type {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .split {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .item {
            display: flex;
            flex-direction: column;
            min-height: 100%;
            padding: 18px;
        }

        .project-card {
            position: relative;
            overflow: hidden;
        }

        .project-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .project-card h2 {
            margin-top: 8px;
        }

        .project-card h2 a {
            color: var(--ink);
            text-decoration: none;
        }

        .project-card h2 a:hover {
            color: var(--primary);
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin: 0 0 10px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border: 1px solid rgba(15, 90, 77, .16);
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-strong);
            font-size: 12px;
            font-weight: 800;
            padding: 3px 9px;
        }

        .pill.neutral {
            border-color: var(--line);
            background: var(--surface-soft);
            color: var(--ink-soft);
        }

        .muted {
            color: var(--ink-soft);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .button,
        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            border: 0;
            border-radius: var(--radius-sm);
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            padding: 11px 16px;
            text-decoration: none;
            transition: background .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .button:hover,
        button:hover {
            background: var(--primary-strong);
            color: #fff;
            box-shadow: 0 10px 24px rgba(15, 90, 77, .18);
            transform: translateY(-1px);
        }

        .button.secondary {
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
        }

        .button.secondary:hover {
            background: var(--surface-soft);
            color: var(--primary);
        }

        label {
            display: block;
            color: var(--ink);
            font-size: 13px;
            font-weight: 800;
            margin-top: 12px;
        }

        input:not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            border: 1px solid #c8d1c7;
            border-radius: var(--radius-sm);
            background: #fff;
            color: var(--ink);
            font: inherit;
            margin-top: 6px;
            padding: 10px 11px;
        }

        input[type="checkbox"],
        input[type="radio"] {
            width: 17px;
            height: 17px;
            margin: 0 8px 0 0;
            vertical-align: -3px;
            accent-color: var(--primary);
        }

        textarea {
            min-height: 116px;
            resize: vertical;
        }

        fieldset {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            margin: 16px 0;
            padding: 16px;
        }

        legend {
            color: var(--ink-soft);
            font-size: 13px;
            font-weight: 800;
            padding: 0 8px;
        }

        .error {
            color: var(--warning);
            font-weight: 700;
            margin: 7px 0 0;
        }

        .notice {
            border-color: rgba(15, 90, 77, .24);
            background: #eef8f2;
            color: var(--primary-strong);
            font-weight: 700;
            margin: 0 0 18px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid rgba(21, 35, 31, .1);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: 0 12px 34px rgba(21, 35, 31, .06);
        }

        table {
            width: 100%;
            min-width: 620px;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 13px 14px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: var(--surface-soft);
            color: var(--ink-soft);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .detail-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 18px;
            align-items: start;
        }

        .fact-list {
            display: grid;
            gap: 10px;
            margin: 0;
        }

        .fact-list div {
            border-bottom: 1px solid var(--line);
            padding-bottom: 10px;
        }

        .fact-list dt {
            color: var(--ink-soft);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .fact-list dd {
            margin: 3px 0 0;
            font-weight: 800;
        }

        .section-stack {
            display: grid;
            gap: 18px;
        }

        .file-list {
            display: grid;
            gap: 9px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .file-list a {
            display: block;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface-soft);
            padding: 11px 12px;
            text-decoration: none;
        }

        .pagination {
            margin-top: 24px;
        }

        @media (max-width: 860px) {
            .site-nav,
            .page-hero,
            .toolbar,
            .split,
            .detail-layout,
            .form-row {
                grid-template-columns: 1fr;
            }

            .site-nav {
                align-items: flex-start;
            }

            .nav-links {
                justify-content: flex-start;
            }

            .page-hero {
                padding: 22px;
            }
        }

        @media (max-width: 560px) {
            .site-nav,
            main {
                width: min(100% - 20px, 1180px);
            }

            .brand {
                grid-template-columns: 36px auto;
            }

            .brand-mark {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            .nav-link {
                padding: 8px 9px;
            }
        }
    </style>
    @livewireStyles
</head>
<body>
<a class="skip-link" href="#content">Przejdź do treści</a>
<header class="site-header">
    <nav class="site-nav" aria-label="Główna nawigacja">
        <a class="brand" href="{{ route('public.projects.index') }}">
            <span class="brand-mark">S</span>
            <span>
                SBO Szczecin
                <span class="brand-subtitle">Budżet obywatelski</span>
            </span>
        </a>
        <div class="nav-links">
            <a class="nav-link" href="{{ route('public.projects.index') }}" @if(request()->routeIs('public.projects.index')) aria-current="page" @endif>Projekty</a>
            <a class="nav-link" href="{{ route('public.projects.map') }}" @if(request()->routeIs('public.projects.map')) aria-current="page" @endif>Mapa</a>
            <a class="nav-link" href="{{ route('public.projects.create') }}" @if(request()->routeIs('public.projects.create')) aria-current="page" @endif>Zgłoś projekt</a>
            <a class="nav-link" href="{{ route('public.voting.welcome') }}" @if(request()->routeIs('public.voting.*')) aria-current="page" @endif>Głosowanie</a>
            <a class="nav-link" href="{{ route('public.results.index') }}" @if(request()->routeIs('public.results.*')) aria-current="page" @endif>Wyniki</a>
            <a class="nav-link" href="{{ route('public.reports.index') }}" @if(request()->routeIs('public.reports.*')) aria-current="page" @endif>Raporty</a>
            <a class="nav-link admin" href="/admin">Admin</a>
        </div>
    </nav>
</header>
<main id="content">
    @if (session('status'))
        <p class="notice">{{ session('status') }}</p>
    @endif

    {{ $slot }}
</main>
@livewireScripts
</body>
</html>

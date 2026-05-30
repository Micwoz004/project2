<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Karta zgłoszeniowa projektu</title>
    <style>
        @page {
            margin: 28px 34px;
        }

        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 6px;
        }

        h2 {
            border-bottom: 1px solid #d1d5db;
            font-size: 14px;
            margin: 18px 0 8px;
            padding-bottom: 4px;
        }

        h3 {
            font-size: 12px;
            margin: 10px 0 4px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
            width: 30%;
        }

        .muted {
            color: #6b7280;
        }

        .value {
            white-space: pre-line;
        }

        .meta {
            margin: 0 0 14px;
        }

        .money {
            text-align: right;
            white-space: nowrap;
        }

        .signatures {
            margin-top: 34px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            display: inline-block;
            margin-right: 32px;
            padding-top: 6px;
            width: 210px;
        }
    </style>
</head>
<body>
@php
    $author = $project->authors ?? [];
    $totalCost = $project->costItems->sum(fn ($item) => (float) $item->amount);
    $category = $project->category?->name ?? $project->categories->first()?->name;
    $projectType = match ((int) $project->local) {
        1 => 'Projekt lokalny',
        2 => 'Projekt Zielonego BO',
        default => 'Projekt miejski',
    };
@endphp

<h1>Karta zgłoszeniowa projektu</h1>
<p class="meta">
    Numer projektu: <strong>{{ $project->number_drawn ?? $project->number ?? $project->id }}</strong><br>
    Status: <strong>{{ $project->publicStatusLabel() }}</strong><br>
    Data zgłoszenia: <strong>{{ $project->submitted_at?->format('d.m.Y H:i') }}</strong>
</p>

<h2>Dane projektu</h2>
<table>
    <tr>
        <th>Tytuł</th>
        <td>{{ $project->title }}</td>
    </tr>
    <tr>
        <th>Edycja</th>
        <td>{{ $project->budgetEdition?->name ?? $project->budgetEdition?->edition ?? 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Obszar</th>
        <td>{{ $project->area?->name ?? 'Całe miasto' }}</td>
    </tr>
    <tr>
        <th>Kategoria</th>
        <td>{{ $category ?? 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Charakter projektu</th>
        <td>{{ $projectType }}</td>
    </tr>
    <tr>
        <th>Lokalizacja</th>
        <td class="value">{{ $project->localization ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Adres / działka</th>
        <td>{{ $project->address ?: 'Nie podano' }}{{ $project->plot ? ' / '.$project->plot : '' }}</td>
    </tr>
</table>

<h2>Opis projektu</h2>
<table>
    <tr>
        <th>Krótki opis</th>
        <td class="value">{{ $project->short_description ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Opis szczegółowy</th>
        <td class="value">{{ $project->description ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Cel</th>
        <td class="value">{{ $project->goal ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Uzasadnienie</th>
        <td class="value">{{ $project->argumentation ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Dostępność</th>
        <td class="value">{{ $project->availability ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Odbiorcy</th>
        <td class="value">{{ $project->recipients ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Bezpłatność</th>
        <td>{{ $project->free_of_charge ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Koszty utrzymania</th>
        <td class="value">{{ $project->additional_cost ?: 'Nie podano' }}</td>
    </tr>
</table>

<h2>Kosztorys</h2>
<table>
    <thead>
        <tr>
            <th style="width: 70%;">Pozycja</th>
            <th style="width: 30%;">Kwota</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($project->costItems as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="money">{{ number_format((float) $item->amount, 2, ',', ' ') }} zł</td>
            </tr>
        @empty
            <tr>
                <td colspan="2">Nie podano pozycji kosztorysu.</td>
            </tr>
        @endforelse
        <tr>
            <th>Razem</th>
            <th class="money">{{ number_format($totalCost, 2, ',', ' ') }} zł</th>
        </tr>
    </tbody>
</table>

<h2>Dane wnioskodawcy</h2>
<table>
    <tr>
        <th>Imię i nazwisko</th>
        <td>{{ trim((string) data_get($author, 'first_name').' '.(string) data_get($author, 'last_name')) ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>E-mail</th>
        <td>{{ data_get($author, 'email') ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Telefon</th>
        <td>{{ data_get($author, 'phone') ?: 'Nie podano' }}</td>
    </tr>
    <tr>
        <th>Adres</th>
        <td>
            {{ data_get($author, 'street') ?: 'Nie podano' }}
            {{ data_get($author, 'house_no') ? data_get($author, 'house_no') : '' }}{{ data_get($author, 'flat_no') ? '/'.data_get($author, 'flat_no') : '' }},
            {{ data_get($author, 'post_code') ?: '' }} {{ data_get($author, 'city') ?: '' }}
        </td>
    </tr>
    <tr>
        <th>Kontakt publiczny</th>
        <td>{{ $project->contact_with ? 'Tak' : 'Nie' }}</td>
    </tr>
</table>

<h2>Współautorzy</h2>
@forelse ($project->coauthors as $coauthor)
    <h3>{{ $loop->iteration }}. {{ trim($coauthor->first_name.' '.$coauthor->last_name) ?: 'Współautor' }}</h3>
    <table>
        <tr>
            <th>E-mail</th>
            <td>{{ $coauthor->email ?: 'Nie podano' }}</td>
        </tr>
        <tr>
            <th>Telefon</th>
            <td>{{ $coauthor->phone ?: 'Nie podano' }}</td>
        </tr>
        <tr>
            <th>Potwierdzony</th>
            <td>{{ $coauthor->confirm ? 'Tak' : 'Nie' }}</td>
        </tr>
    </table>
@empty
    <p class="muted">Nie podano współautorów.</p>
@endforelse

<h2>Załączniki</h2>
<table>
    <thead>
        <tr>
            <th style="width: 38%;">Typ</th>
            <th style="width: 62%;">Nazwa pliku</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($project->files as $file)
            <tr>
                <td>{{ $file->type?->label() ?? 'Załącznik' }}</td>
                <td>{{ $file->original_name }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2">Brak załączników.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<h2>Oświadczenia</h2>
<table>
    <tr>
        <th>Zgoda na modyfikację projektu</th>
        <td>{{ $project->consent_to_change ? 'Tak' : 'Nie' }}</td>
    </tr>
    <tr>
        <th>Załączniki zanonimizowane</th>
        <td>{{ $project->attachments_anonymized ? 'Tak' : 'Nie' }}</td>
    </tr>
    <tr>
        <th>Lista poparcia</th>
        <td>{{ $project->is_support_list ? 'Wymagana / załączona' : 'Nie dotyczy' }}</td>
    </tr>
</table>

<div class="signatures">
    <span class="signature-line">Podpis wnioskodawcy</span>
    <span class="signature-line">Data</span>
</div>
</body>
</html>

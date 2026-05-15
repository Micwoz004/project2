<x-public.layout :title="$project->title">
    <p><a href="{{ route('public.projects.index') }}">Powrót do listy</a></p>
    <h1>{{ $project->title }}</h1>
    <p class="muted">
        {{ $project->area?->name ?? 'Bez obszaru' }}
        @if ($project->number_drawn)
            · nr {{ $project->number_drawn }}
        @endif
        · {{ $project->publicStatusLabel() }}
    </p>

    <section class="panel">
        <h2>Lokalizacja</h2>
        <p>{{ $project->localization }}</p>

        <h2>Opis</h2>
        <p>{{ $project->description }}</p>

        <h2>Cel</h2>
        <p>{{ $project->goal }}</p>

        <h2>Uzasadnienie</h2>
        <p>{{ $project->argumentation }}</p>
    </section>

    <section>
        <h2>Koszty</h2>
        <table>
            <thead>
            <tr>
                <th>Pozycja</th>
                <th>Kwota</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($project->costItems as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format((float) $item->amount, 2, ',', ' ') }} zł</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
</x-public.layout>

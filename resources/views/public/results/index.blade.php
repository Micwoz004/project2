<x-public.layout title="Wyniki">
    <h1>Wyniki</h1>

    @if (! $edition)
        <p class="panel">Brak skonfigurowanej edycji.</p>
    @elseif (! $resultsPublished)
        <p class="panel">Wyniki nie zostały jeszcze opublikowane.</p>
    @else
        <p><a class="button" href="{{ route('public.results.export') }}">Pobierz CSV</a></p>

        <table>
            <thead>
            <tr>
                <th>Projekt</th>
                <th>Obszar</th>
                <th>Punkty</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($totals as $row)
                @php($project = $projects->get($row->project_id))
                <tr>
                    <td>{{ $project?->title ?? 'Projekt ' . $row->project_id }}</td>
                    <td>{{ $project?->area?->name ?? '-' }}</td>
                    <td>{{ $row->points }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Brak oddanych głosów.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @endif
</x-public.layout>

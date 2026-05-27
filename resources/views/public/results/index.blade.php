<x-public.layout title="Wyniki">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Wyniki</h1>
            <p class="page-summary">Publiczne wyniki są pokazywane po osiągnięciu etapu publikacji edycji i obejmują wyłącznie zaakceptowane karty głosowania.</p>
        </div>
        @if ($edition && $resultsPublished)
            <div class="actions">
                <a class="button" href="{{ route('public.results.export') }}">Pobierz CSV</a>
            </div>
        @endif
    </section>

    @if (! $edition)
        <p class="panel">Brak skonfigurowanej edycji.</p>
    @elseif (! $resultsPublished)
        <p class="panel">Wyniki nie zostały jeszcze opublikowane.</p>
    @else
        <section class="section">
            <div class="section-heading">
                <div>
                    <h2>Ranking projektów</h2>
                    <p>Zestawienie punktów naliczonych dla projektów w aktualnie publikowanej edycji.</p>
                </div>
            </div>
        </section>
        <div class="table-wrap">
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
                        <td><strong>{{ $row->points }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Brak oddanych głosów.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</x-public.layout>

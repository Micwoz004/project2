<x-public.layout title="Mapa projektów SBO">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Mapa projektów SBO Szczecin</h1>
            <p class="page-summary">Lista projektów z rozpoznaną lokalizacją. Dane mapy pozostają zgodne z legacy i mogą pochodzić ze współrzędnych albo zapisów JSON.</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('public.projects.index') }}">Lista projektów</a>
        </div>
    </section>

    <form class="toolbar" method="get" action="{{ route('public.projects.map') }}">
        <div>
            <label for="q">Szukaj</label>
            <input id="q" name="q" value="{{ request('q') }}" maxlength="120">
        </div>

        <div>
            <label for="budget_edition_id">Edycja</label>
            <select id="budget_edition_id" name="budget_edition_id">
                <option value="">Wszystkie</option>
                @foreach ($budgetEditions as $edition)
                    <option value="{{ $edition->id }}" @selected((int) request('budget_edition_id') === $edition->id)>
                        {{ $edition->propose_start->format('Y-m-d') }} - {{ $edition->result_announcement_end->format('Y-m-d') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="area_id">Obszar</label>
            <select id="area_id" name="area_id">
                <option value="">Wszystkie</option>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}" @selected((int) request('area_id') === $area->id)>
                        {{ $area->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="category_id">Kategoria</label>
            <select id="category_id" name="category_id">
                <option value="">Wszystkie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) request('category_id') === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit">Filtruj mapę</button>
    </form>

    @php
        $mapPayload = $mapProjects->map(fn (array $item): array => [
            'id' => $item['project']->id,
            'title' => $item['project']->title,
            'lat' => $item['lat'],
            'lng' => $item['lng'],
            'url' => route('public.projects.show', $item['project']),
        ]);
    @endphp

    <section class="panel" data-map-projects-count="{{ $mapPayload->count() }}">
        <h2>Projekty z lokalizacją</h2>

        @if ($mapProjects->isEmpty())
            <p class="empty-state">Brak publicznych projektów z danymi mapy dla wybranych filtrów.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Projekt</th>
                        <th>Obszar</th>
                        <th>Współrzędne</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($mapProjects as $item)
                        <tr>
                            <td>
                                @if ($item['project']->number_drawn)
                                    nr {{ $item['project']->number_drawn }} ·
                                @endif
                                <a href="{{ route('public.projects.show', $item['project']) }}">{{ $item['project']->title }}</a>
                            </td>
                            <td>{{ $item['project']->area?->name ?? 'Bez obszaru' }}</td>
                            <td>{{ number_format($item['lat'], 7, '.', '') }}, {{ number_format($item['lng'], 7, '.', '') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <script id="map-projects-data" type="application/json">@json($mapPayload)</script>
</x-public.layout>

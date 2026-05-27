<x-public.layout title="Projekty SBO">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Projekty mieszkańców</h1>
            <p class="page-summary">Katalog publicznych propozycji z czytelnym filtrowaniem, statusem i szybkim przejściem do mapy lub szczegółów projektu.</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('public.projects.map') }}">Mapa projektów</a>
            <a class="button" href="{{ route('public.projects.create') }}">Zgłoś projekt</a>
        </div>
    </section>

    <form class="toolbar" method="get" action="{{ route('public.projects.index') }}">
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

        <button type="submit">Filtruj</button>
    </form>

    <section class="section projects-shell">
        <div class="section-heading">
            <div>
                <h2>Katalog projektów</h2>
                <p>Lista obejmuje projekty widoczne publicznie i uporządkowane zgodnie z numeracją edycji.</p>
            </div>
        </div>
    </section>

    <div class="project-grid-strong">
        @forelse ($projects as $project)
            <article class="item project-card">
                <p class="meta">
                    <span class="pill">{{ $project->area?->name ?? 'Bez obszaru' }}</span>
                    @if ($project->budgetEdition)
                        <span class="pill neutral">Edycja {{ $project->budgetEdition->propose_start->format('Y') }}</span>
                    @endif
                    @if ($project->number_drawn)
                        <span class="pill neutral">Nr {{ $project->number_drawn }}</span>
                    @endif
                </p>
                <h2><a href="{{ route('public.projects.show', $project) }}">{{ $project->title }}</a></h2>
                <p>{{ $project->short_description ?: str($project->description)->limit(220) }}</p>
                <p class="muted project-card-status">{{ $project->publicStatusLabel() }}</p>
                <p class="actions"><a class="button secondary" href="{{ route('public.projects.show', $project) }}">Szczegóły</a></p>
            </article>
        @empty
            <p class="empty-state">Brak projektów spełniających kryteria.</p>
        @endforelse
    </div>

    <div class="pagination">{{ $projects->links() }}</div>
</x-public.layout>

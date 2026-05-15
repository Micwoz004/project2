<x-public.layout title="Projekty SBO">
    <h1>Projekty SBO Szczecin</h1>

    <form class="toolbar" method="get" action="{{ route('public.projects.index') }}">
        <label for="q">Szukaj</label>
        <input id="q" name="q" value="{{ request('q') }}" maxlength="120">

        <label for="budget_edition_id">Edycja</label>
        <select id="budget_edition_id" name="budget_edition_id">
            <option value="">Wszystkie</option>
            @foreach ($budgetEditions as $edition)
                <option value="{{ $edition->id }}" @selected((int) request('budget_edition_id') === $edition->id)>
                    {{ $edition->propose_start->format('Y-m-d') }} - {{ $edition->result_announcement_end->format('Y-m-d') }}
                </option>
            @endforeach
        </select>

        <label for="area_id">Obszar</label>
        <select id="area_id" name="area_id">
            <option value="">Wszystkie</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected((int) request('area_id') === $area->id)>
                    {{ $area->name }}
                </option>
            @endforeach
        </select>

        <label for="category_id">Kategoria</label>
        <select id="category_id" name="category_id">
            <option value="">Wszystkie</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) request('category_id') === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <p><button type="submit">Filtruj</button></p>
    </form>

    <div class="grid">
        @forelse ($projects as $project)
            <article class="item">
                <p class="muted">
                    {{ $project->area?->name ?? 'Bez obszaru' }}
                    @if ($project->budgetEdition)
                        · edycja {{ $project->budgetEdition->propose_start->format('Y') }}
                    @endif
                    @if ($project->number_drawn)
                        · nr {{ $project->number_drawn }}
                    @endif
                </p>
                <h2><a href="{{ route('public.projects.show', $project) }}">{{ $project->title }}</a></h2>
                <p>{{ $project->short_description ?: str($project->description)->limit(220) }}</p>
                <p class="muted">{{ $project->publicStatusLabel() }}</p>
            </article>
        @empty
            <p class="panel">Brak projektów spełniających kryteria.</p>
        @endforelse
    </div>

    {{ $projects->links() }}
</x-public.layout>

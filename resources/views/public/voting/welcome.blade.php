<x-public.layout title="Głosowanie">
    <h1>Głosowanie</h1>

    <section class="panel">
        @if ($edition)
            <p>Okno głosowania: {{ $edition->voting_start->format('Y-m-d H:i') }} - {{ $edition->voting_end->format('Y-m-d H:i') }}</p>
        @else
            <p>Brak skonfigurowanej edycji głosowania.</p>
        @endif
    </section>

    <h2>Projekty lokalne</h2>
    <div class="grid">
        @forelse ($localProjects as $project)
            <article class="item">
                <p class="muted">{{ $project->area?->name }} · nr {{ $project->number_drawn ?? $project->number }}</p>
                <h2>{{ $project->title }}</h2>
            </article>
        @empty
            <p class="panel">Brak projektów lokalnych na liście do głosowania.</p>
        @endforelse
    </div>

    <h2>Projekty ogólnomiejskie</h2>
    <div class="grid">
        @forelse ($citywideProjects as $project)
            <article class="item">
                <p class="muted">{{ $project->area?->name }} · nr {{ $project->number_drawn ?? $project->number }}</p>
                <h2>{{ $project->title }}</h2>
            </article>
        @empty
            <p class="panel">Brak projektów ogólnomiejskich na liście do głosowania.</p>
        @endforelse
    </div>
</x-public.layout>

<x-public.layout title="Ogłoszenia">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Ogłoszenia</h1>
            <p class="page-summary">Komunikaty organizacyjne dotyczące budżetu obywatelskiego, terminów, list projektów i wyników.</p>
        </div>
    </section>

    <div class="grid">
        @forelse ($announcements as $announcement)
            <article class="announcement-card">
                <p class="meta">
                    <span class="pill">{{ $announcement->published_at?->format('d.m.Y') ?? 'Komunikat' }}</span>
                    @if ($announcement->budget_edition_id)
                        <span class="pill neutral">Edycja {{ $announcement->budget_edition_id }}</span>
                    @endif
                </p>
                <h2><a href="{{ route('public.announcements.show', $announcement->slug) }}">{{ $announcement->title }}</a></h2>
                <p class="muted">{{ $announcement->lead ?: str(strip_tags($announcement->body))->limit(180) }}</p>
                <p class="actions"><a class="button secondary" href="{{ route('public.announcements.show', $announcement->slug) }}">Czytaj więcej</a></p>
            </article>
        @empty
            <p class="empty-state">Brak opublikowanych ogłoszeń.</p>
        @endforelse
    </div>

    <div class="pagination">{{ $announcements->links() }}</div>
</x-public.layout>

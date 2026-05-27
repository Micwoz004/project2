<x-public.layout :title="$announcement->title">
    <section class="page-hero">
        <div>
            <p><a href="{{ route('public.announcements.index') }}">Powrót do ogłoszeń</a></p>
            <h1 class="page-title">{{ $announcement->title }}</h1>
            <p class="page-summary">{{ $announcement->lead }}</p>
            <p class="meta">
                <span class="pill">{{ $announcement->published_at?->format('d.m.Y H:i') ?? 'Komunikat' }}</span>
            </p>
        </div>
    </section>

    <article class="panel content-body">
        {!! $announcement->body !!}
    </article>
</x-public.layout>

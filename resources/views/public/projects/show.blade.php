<x-public.layout :title="$project->title">
    <section class="page-hero">
        <div>
            <p><a href="{{ route('public.projects.index') }}">Powrót do listy</a></p>
            <h1 class="page-title">{{ $project->title }}</h1>
            <p class="meta">
                <span class="pill">{{ $project->area?->name ?? 'Bez obszaru' }}</span>
                @if ($project->number_drawn)
                    <span class="pill neutral">Nr {{ $project->number_drawn }}</span>
                @endif
                <span class="pill neutral">{{ $project->publicStatusLabel() }}</span>
            </p>
        </div>
    </section>

    <div class="detail-layout">
        <div class="section-stack">
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
                <div class="table-wrap">
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
                </div>
            </section>
        </div>

        <section class="panel">
            <h2>Informacje</h2>
            <dl class="fact-list">
                <div>
                    <dt>Obszar</dt>
                    <dd>{{ $project->area?->name ?? 'Bez obszaru' }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $project->publicStatusLabel() }}</dd>
                </div>
                <div>
                    <dt>Numer</dt>
                    <dd>{{ $project->number_drawn ?? $project->number ?? '-' }}</dd>
                </div>
            </dl>

            @if ($project->publicFiles->isNotEmpty())
                <h2>Załączniki</h2>
                <ul class="file-list">
                    @foreach ($project->publicFiles as $file)
                        <li><a href="{{ $file->publicUrl() }}">{{ $file->original_name }}</a></li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-public.layout>

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

            <section class="panel comments-panel">
                <div class="comments-heading">
                    <div>
                        <h2>Komentarze</h2>
                        <p class="muted">Publiczna dyskusja przy projekcie zachowuje moderację i widoczność ze starego systemu.</p>
                    </div>
                    <span class="pill neutral">{{ $publicComments->count() }}</span>
                </div>

                @if ($errors->has('comment') || $errors->has('content') || $errors->has('parent_id'))
                    <p class="error">{{ $errors->first('comment') ?: $errors->first('content') ?: $errors->first('parent_id') }}</p>
                @endif

                @auth
                    <form class="comment-form" method="post" action="{{ route('public.projects.comments.store', $project) }}">
                        @csrf
                        <label for="comment-content">Dodaj komentarz</label>
                        <textarea id="comment-content" name="content" maxlength="200" required>{{ old('content') }}</textarea>
                        <p class="actions"><button type="submit">Dodaj komentarz</button></p>
                    </form>
                @else
                    <p class="notice inline-notice">Komentarze mogą dodawać zalogowani użytkownicy z rolą wnioskodawcy.</p>
                @endauth

                <div class="comment-list">
                    @forelse ($publicComments as $comment)
                        <article class="comment" id="comment-{{ $comment->id }}">
                            <div class="comment-meta">
                                <strong>{{ $comment->creator?->name ?? 'Użytkownik' }}</strong>
                                <span>{{ $comment->created_at?->format('Y-m-d H:i') }}</span>
                                @if (! $comment->moderated)
                                    <span class="pill neutral">Oczekuje</span>
                                @endif
                                @if ($comment->hidden)
                                    <span class="pill neutral">Ukryty</span>
                                @endif
                                @if ($comment->admin_hidden)
                                    <span class="pill neutral">Ukryty administracyjnie</span>
                                @endif
                            </div>
                            @if ($comment->parent_id)
                                <p class="muted">Odpowiedź do komentarza #{{ $comment->parent_id }}</p>
                            @endif
                            <p>{{ $comment->content }}</p>

                            @auth
                                <form class="comment-reply-form" method="post" action="{{ route('public.projects.comments.store', $project) }}">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                    <label for="reply-{{ $comment->id }}">Odpowiedz</label>
                                    <textarea id="reply-{{ $comment->id }}" name="content" maxlength="200" required></textarea>
                                    <p class="actions"><button class="secondary" type="submit">Odpowiedz</button></p>
                                </form>
                            @endauth

                            @if (auth()->id() === $comment->created_by_id)
                                <div class="comment-owner-tools">
                                    <details>
                                        <summary>Edytuj komentarz</summary>
                                        <form method="post" action="{{ route('public.projects.comments.update', [$project, $comment]) }}">
                                            @csrf
                                            @method('PUT')
                                            <label for="edit-comment-{{ $comment->id }}">Treść komentarza</label>
                                            <textarea id="edit-comment-{{ $comment->id }}" name="content" maxlength="200" required>{{ old('content', $comment->content) }}</textarea>
                                            <p class="actions"><button type="submit">Zapisz komentarz</button></p>
                                        </form>
                                    </details>
                                    <form method="post" action="{{ route('public.projects.comments.toggle-hidden', [$project, $comment]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="secondary" type="submit">{{ $comment->hidden ? 'Przywróć komentarz' : 'Ukryj komentarz' }}</button>
                                    </form>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="empty-state">Brak widocznych komentarzy.</p>
                    @endforelse
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

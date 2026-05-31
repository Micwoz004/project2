@php
    use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionState;

    $stateLabel = match ($state) {
        BudgetEditionState::Propose => 'Trwa nabór projektów',
        BudgetEditionState::PreVotingVerification => 'Trwa weryfikacja projektów',
        BudgetEditionState::PreVotingCorrection => 'Przygotowanie listy do głosowania',
        BudgetEditionState::Voting => 'Trwa głosowanie',
        BudgetEditionState::PostVotingVerification => 'Weryfikacja wyników',
        BudgetEditionState::ResultAnnouncement => 'Publikacja wyników',
        default => 'Edycja poza aktywnym etapem',
    };
    $stateDate = match ($state) {
        BudgetEditionState::Propose => $edition?->propose_end,
        BudgetEditionState::PreVotingVerification => $edition?->pre_voting_verification_end,
        BudgetEditionState::Voting => $edition?->voting_end,
        BudgetEditionState::PostVotingVerification => $edition?->post_voting_verification_end,
        BudgetEditionState::ResultAnnouncement => $edition?->result_announcement_end,
        default => $edition?->propose_start,
    };
    $timeline = [
        ['state' => BudgetEditionState::Propose, 'label' => 'Zgłaszanie', 'date' => $edition?->propose_end],
        ['state' => BudgetEditionState::PreVotingVerification, 'label' => 'Weryfikacja', 'date' => $edition?->pre_voting_verification_end],
        ['state' => BudgetEditionState::PreVotingCorrection, 'label' => 'Lista projektów', 'date' => $edition?->voting_start],
        ['state' => BudgetEditionState::Voting, 'label' => 'Głosowanie', 'date' => $edition?->voting_end],
        ['state' => BudgetEditionState::ResultAnnouncement, 'label' => 'Wyniki', 'date' => $edition?->result_announcement_end],
    ];
@endphp

<x-public.layout title="Budżet Obywatelski">
    <section class="home-hero">
        <div class="home-hero-copy home-hero-copy-strong">
            <p class="hero-intro">Mieszkańcy zgłaszają pomysły. Miasto je weryfikuje. Ostatecznie decyduje głosowanie.</p>
            <h1>Portal budżetu obywatelskiego, który prowadzi od pomysłu do realizacji.</h1>
            <p>Nowoczesna, publiczna strefa mieszkańca z miejscem na komunikaty miasta, harmonogram edycji, katalog projektów i prosty start zgłoszenia.</p>
            <div class="hero-actions">
                <a class="button coral" href="{{ route('public.projects.create') }}">Zgłoś projekt</a>
                <a class="button secondary" href="{{ route('public.projects.index') }}">Przeglądaj projekty</a>
            </div>
            <div class="hero-marquee" aria-label="Najważniejsze informacje">
                <div>
                    <span>Aktualny etap</span>
                    <strong>{{ $stateLabel }}</strong>
                </div>
                <div>
                    <span>Najbliższy termin</span>
                    <strong>{{ $stateDate?->format('d.m.Y') ?? 'Wkrótce' }}</strong>
                </div>
                <div>
                    <span>W puli projektów</span>
                    <strong>{{ $projectCount }}</strong>
                </div>
            </div>
        </div>
        <div class="home-hero-stage">
            <div class="home-hero-media" role="img" aria-label="Ilustracja mieszkańców i miejskich projektów"></div>
            <div class="hero-stage-card">
                <p>Trwa bieżąca edycja</p>
                <strong>{{ $stateLabel }}</strong>
                <span>Najbliższy termin: {{ $stateDate?->format('d.m.Y') ?? 'Wkrótce' }}</span>
            </div>
            <div class="hero-stage-list">
                <article>
                    <span>Ogłoszenia</span>
                    <strong>{{ $announcements->count() }}</strong>
                    <p>Aktualne komunikaty i decyzje organizacyjne.</p>
                </article>
                <article>
                    <span>Do głosowania</span>
                    <strong>{{ $pickedCount }}</strong>
                    <p>Publiczne projekty gotowe do przeglądania i wyboru.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section civic-overview">
        <div class="overview-story">
            <p class="overview-label">Jak działa budżet obywatelski</p>
            <h2>Jedna platforma dla zgłoszeń, komunikatów i decyzji mieszkańców.</h2>
            <p>Serwis ma być czytelny już od pierwszego wejścia. Dlatego porządkuje proces w trzy warstwy: informacja miejska, katalog projektów i działania mieszkańca.</p>
        </div>
        <div class="overview-panels">
            <article class="overview-panel accent">
                <span>Informacja miejska</span>
                <strong>Ogłoszenia, terminy, zasady i harmonogram edycji.</strong>
            </article>
            <article class="overview-panel">
                <span>Strefa projektów</span>
                <strong>Lista propozycji, mapa lokalizacji i status każdego projektu.</strong>
            </article>
            <article class="overview-panel">
                <span>Strefa działania</span>
                <strong>Zgłaszanie pomysłów, poprawki, głosowanie i wyniki.</strong>
            </article>
        </div>
    </section>

    <section class="section timeline-section" id="harmonogram">
        <div class="section-heading">
            <div>
                <h2>Harmonogram edycji</h2>
                <p>Najważniejsze etapy procesu w jednym miejscu. Sekcja ma wyglądać jak miejski plan kampanii, a nie zwykła lista dat.</p>
            </div>
            <a class="button secondary" href="{{ route('public.info.show', 'harmonogram') }}">Szczegóły</a>
        </div>
        <div class="timeline">
            @foreach ($timeline as $item)
                <div class="timeline-item @if($state === $item['state']) is-active @endif">
                    <span>{{ $loop->iteration }}</span>
                    <strong>{{ $item['label'] }}</strong>
                    <p>{{ $item['date']?->format('d.m.Y') ?? 'Data do ustalenia' }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Ogłoszenia</h2>
                <p>Publiczne komunikaty miasta dotyczące naboru, list projektów, głosowania i wyników.</p>
            </div>
            <a class="button secondary" href="{{ route('public.announcements.index') }}">Wszystkie ogłoszenia</a>
        </div>
        <div class="announcement-feed">
            @forelse ($announcements as $announcement)
                <article class="announcement-card">
                    <p class="meta"><span class="pill">{{ $announcement->published_at?->format('d.m.Y') ?? 'Komunikat' }}</span></p>
                    <div>
                        <h2><a href="{{ route('public.announcements.show', $announcement->slug) }}">{{ $announcement->title }}</a></h2>
                        <p class="muted">{{ $announcement->lead ?: str(strip_tags($announcement->body))->limit(160) }}</p>
                    </div>
                    <a class="button secondary" href="{{ route('public.announcements.show', $announcement->slug) }}">Czytaj</a>
                </article>
            @empty
                <article class="announcement-card">
                    <p class="meta"><span class="pill">Start</span></p>
                    <div>
                        <h2>Wkrótce pojawią się pierwsze ogłoszenia</h2>
                        <p class="muted">Po publikacji w panelu administracyjnym komunikaty będą widoczne w tym miejscu.</p>
                    </div>
                </article>
                <article class="announcement-card">
                    <p class="meta"><span class="pill neutral">Informacja</span></p>
                    <div>
                        <h2>Sprawdź harmonogram</h2>
                        <p class="muted">Daty naboru, weryfikacji i głosowania są prezentowane na podstawie aktywnej edycji.</p>
                    </div>
                </article>
            @endforelse
        </div>
    </section>

    <section class="section home-dual-grid">
        <div>
            <div class="section-heading">
                <div>
                    <h2>Co warto wiedzieć</h2>
                    <p>Najkrótsza droga od pomysłu do projektu wybranego przez mieszkańców.</p>
                </div>
            </div>
            <div class="feature-grid">
                <article class="info-tile">
                    <span class="info-tile-icon">01</span>
                    <h3>Pomysł powinien służyć mieszkańcom</h3>
                    <p class="muted">Projekt opisuje potrzebę, miejsce realizacji, odbiorców i szacunkowy koszt.</p>
                </article>
                <article class="info-tile">
                    <span class="info-tile-icon">02</span>
                    <h3>Weryfikacja sprawdza wykonalność</h3>
                    <p class="muted">Po zgłoszeniu projekt przechodzi ocenę formalną i merytoryczną.</p>
                </article>
                <article class="info-tile">
                    <span class="info-tile-icon">03</span>
                    <h3>Decyduje głosowanie</h3>
                    <p class="muted">Projekty dopuszczone do głosowania trafiają na publiczną listę.</p>
                </article>
            </div>
        </div>
        <div class="process-poster">
            <p>Ścieżka projektu</p>
            <h2>Pomysł. Weryfikacja. Głosowanie. Wynik.</h2>
            <p class="muted">To miejsce na bardziej emocjonalny, kampanijny komunikat miasta, który tłumaczy sens budżetu obywatelskiego prostym językiem.</p>
            <a class="button" href="{{ route('public.info.show', 'o-budzecie') }}">Poznaj zasady</a>
        </div>
    </section>

    <section class="section project-journey">
        <div class="section-heading">
            <div>
                <h2>Jak zgłosić projekt</h2>
                <p>Formularz prowadzi przez dane autora, opis projektu, kosztorys, współautorów i załączniki.</p>
            </div>
            <a class="button" href="{{ route('public.projects.create') }}">Przejdź do formularza</a>
        </div>
        <div class="feature-grid feature-grid-steps">
            <article class="info-tile">
                <span class="step-number">1</span>
                <h3>Opisz pomysł</h3>
                <p class="muted">Podaj tytuł, lokalizację, cel, uzasadnienie i dostępność projektu.</p>
            </article>
            <article class="process-step">
                <span class="step-number">2</span>
                <h3>Dodaj koszty i załączniki</h3>
                <p class="muted">Uzupełnij kosztorys oraz wymagane dokumenty, w tym listę poparcia.</p>
            </article>
            <article class="process-step">
                <span class="step-number">3</span>
                <h3>Wyślij do weryfikacji</h3>
                <p class="muted">Po zgłoszeniu projekt otrzyma status i trafi do dalszej obsługi.</p>
            </article>
        </div>
    </section>

    <section class="section map-teaser">
        <div class="map-teaser-copy">
            <h2>Projekty na mapie miasta</h2>
            <p class="muted">Przeglądaj projekty według obszaru, kategorii i lokalizacji. Ta sekcja ma prowadzić do przestrzennego przeglądania projektów, a nie tylko do tabeli.</p>
            <div class="actions">
                <a class="button" href="{{ route('public.projects.map') }}">Otwórz mapę</a>
                <a class="button secondary" href="{{ route('public.projects.index') }}">Lista projektów</a>
            </div>
        </div>
        <div class="map-teaser-media" role="img" aria-label="Ilustracja mapy projektów"></div>
    </section>
</x-public.layout>

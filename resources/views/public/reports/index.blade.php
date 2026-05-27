<x-public.layout title="Raporty publiczne">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Raporty publiczne</h1>
            <p class="page-summary">Zestawienia bez danych wrażliwych, przygotowywane na podstawie zaakceptowanych kart głosowania i widocznych projektów danej edycji.</p>
        </div>
    </section>

    <div class="feature-grid">
        <article class="info-tile">
            <span class="info-tile-icon">CSV</span>
            <h3>Wyniki publiczne</h3>
            <p class="muted">Eksport wyników jest dostępny po publikacji wyników edycji.</p>
            <p><a class="button secondary" href="{{ route('public.results.index') }}">Przejdź do wyników</a></p>
        </article>
        <article class="info-tile">
            <span class="info-tile-icon">BO</span>
            <h3>Raporty edycji</h3>
            <p class="muted">Miejsce na publiczne raporty i podsumowania przygotowane przez urząd.</p>
        </article>
        <article class="info-tile">
            <span class="info-tile-icon">PII</span>
            <h3>Bez danych wrażliwych</h3>
            <p class="muted">Publiczna część prezentuje wyłącznie dane możliwe do publikacji.</p>
        </article>
    </div>
</x-public.layout>

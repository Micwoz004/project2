<x-public.layout title="Głosowanie">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Głosowanie</h1>
            <p class="page-summary">Pobierz kod SMS, wybierz projekt lokalny i ogólnomiejski, a następnie zapisz głos w aktywnej edycji budżetu.</p>
        </div>
    </section>

    <section class="section">
        <div class="feature-grid">
            <article class="info-tile">
                <span class="info-tile-icon">SMS</span>
                <h3>Potwierdź tożsamość</h3>
                <p class="muted">Kod SMS zabezpiecza głosowanie i jest wymagany przed oddaniem głosu.</p>
            </article>
            <article class="info-tile">
                <span class="info-tile-icon">✓</span>
                <h3>Wybierz projekty</h3>
                <p class="muted">Możesz wskazać projekt lokalny i ogólnomiejski zgodnie z regułami edycji.</p>
            </article>
            <article class="info-tile">
                <span class="info-tile-icon">ID</span>
                <h3>Zapisz kartę</h3>
                <p class="muted">Po poprawnej weryfikacji system zapisze kartę głosowania.</p>
            </article>
        </div>
    </section>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <livewire:public-voting-flow />
</x-public.layout>

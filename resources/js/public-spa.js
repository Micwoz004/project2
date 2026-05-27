const state = window.BO_SPA || {};
const root = document.getElementById('bo-spa-root');
let cleanupNavDropdown = null;

const t = {
    pl: {
        navHome: 'Start',
        navProjects: 'Projekty',
        navInfo: 'Informacje',
        navSubmit: 'Zgłoś projekt',
        searchPlaceholder: 'Szukaj po nazwie, obszarze lub kategorii',
        noResults: 'Nie znaleziono projektów dla tych filtrów.',
        saved: 'Zapisano wybór w tej sesji.',
        languageChanged: 'Zmieniono język interfejsu.',
        contrastOn: 'Włączono wysoki kontrast.',
        contrastOff: 'Wyłączono wysoki kontrast.',
        fontLarge: 'Włączono większy tekst.',
        fontBase: 'Przywrócono standardowy rozmiar tekstu.',
    },
    en: {
        navHome: 'Home',
        navProjects: 'Projects',
        navInfo: 'Information',
        navSubmit: 'Submit project',
        searchPlaceholder: 'Search by title, area, or category',
        noResults: 'No projects match these filters.',
        saved: 'Choice saved in this session.',
        languageChanged: 'Interface language changed.',
        contrastOn: 'High contrast enabled.',
        contrastOff: 'High contrast disabled.',
        fontLarge: 'Larger text enabled.',
        fontBase: 'Standard text size restored.',
    },
    uk: {
        navHome: 'Головна',
        navProjects: 'Проєкти',
        navInfo: 'Інформація',
        navSubmit: 'Подати проєкт',
        searchPlaceholder: 'Шукайте за назвою, районом або категорією',
        noResults: 'Немає проєктів для вибраних фільтрів.',
        saved: 'Вибір збережено в цій сесії.',
        languageChanged: 'Мову інтерфейсу змінено.',
        contrastOn: 'Увімкнено високий контраст.',
        contrastOff: 'Вимкнено високий контраст.',
        fontLarge: 'Увімкнено більший текст.',
        fontBase: 'Повернено стандартний розмір тексту.',
    },
    de: {
        navHome: 'Start',
        navProjects: 'Projekte',
        navInfo: 'Informationen',
        navSubmit: 'Projekt einreichen',
        searchPlaceholder: 'Nach Titel, Bereich oder Kategorie suchen',
        noResults: 'Keine Projekte für diese Filter gefunden.',
        saved: 'Auswahl in dieser Sitzung gespeichert.',
        languageChanged: 'Sprache der Oberfläche geändert.',
        contrastOn: 'Hoher Kontrast aktiviert.',
        contrastOff: 'Hoher Kontrast deaktiviert.',
        fontLarge: 'Größerer Text aktiviert.',
        fontBase: 'Standard-Textgröße wiederhergestellt.',
    },
};

function copy() {
    return t[document.documentElement.lang] || t.pl;
}

function langLabel(lang) {
    return {
        pl: 'PL',
        uk: 'UA',
        en: 'EN',
        de: 'DE',
    }[lang] || lang.toUpperCase();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function stripHtml(value) {
    const node = document.createElement('div');
    node.innerHTML = String(value ?? '');
    return node.textContent || node.innerText || '';
}

function html(value) {
    return String(value ?? '');
}

function href(key) {
    return state.links?.[key] || '#';
}

function currentPath() {
    return window.location.pathname.replace(/\/$/, '') || '/';
}

function queryValue(name) {
    return new URLSearchParams(window.location.search).get(name) || '';
}

function active(path) {
    const current = currentPath();
    if (path === '/') return current === '/';
    return current === path || current.startsWith(`${path}/`);
}

const infoMenuSlugs = [
    'o-budzecie',
    'budzet-krok-po-kroku',
    'harmonogram',
    'zglaszanie-projektow',
    'glosowanie',
    'faq',
    'pomoc-i-kontakt',
];

function infoMenuItems() {
    const pages = state.pages || [];
    const selected = infoMenuSlugs
        .map((slug) => pages.find((page) => page.slug === slug))
        .filter(Boolean);

    return (selected.length ? selected : pages.slice(0, 7)).map((page) => ({
        title: page.title,
        url: page.url || `/informacje/${page.slug}`,
    }));
}

function renderInfoDropdown(label) {
    const items = infoMenuItems();
    const isActive = active('/informacje');

    return `
        <li class="nav-item nav-item-dropdown">
            <button class="nav-link nav-dropdown-toggle" type="button" data-nav-dropdown aria-expanded="false" ${isActive ? 'aria-current="page"' : ''}>
                <span>${label}</span>
            </button>
            <div class="nav-dropdown-menu" data-nav-dropdown-menu>
                <p class="nav-dropdown-eyebrow">Najważniejsze informacje</p>
                <ul>
                    ${items.map((item) => `
                        <li>
                            <a class="nav-dropdown-link" href="${escapeHtml(item.url)}" data-spa-link>
                                ${escapeHtml(item.title)}
                            </a>
                        </li>
                    `).join('')}
                </ul>
            </div>
        </li>
    `;
}

function projectIcon(category) {
    const label = String(category || '').toLocaleLowerCase('pl-PL');
    if (label.includes('sport')) {
        return '<svg viewBox="0 0 48 48"><path d="M24 42a18 18 0 1 0 0-36 18 18 0 0 0 0 36ZM10 25c8-2 20-2 28 0M24 6c-5 7-5 29 0 36M36 12c-7 5-17 5-24 0" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    }
    if (label.includes('kultur') || label.includes('bibli')) {
        return '<svg viewBox="0 0 48 48"><path d="M10 12h11c4 0 7 3 7 7v19c0-4-3-7-7-7H10Zm28 0H28c-4 0-7 3-7 7v19c0-4 3-7 7-7h10Z" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    }
    if (label.includes('mobil') || label.includes('dro') || label.includes('rower')) {
        return '<svg viewBox="0 0 48 48"><path d="M16 34a7 7 0 1 0 0-14 7 7 0 0 0 0 14Zm16 0a7 7 0 1 0 0-14 7 7 0 0 0 0 14ZM21 27h6l-5-11h7" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    }
    return '<svg viewBox="0 0 48 48"><path d="M24 42V22M24 22c-8 0-12-5-12-11 8 0 12 5 12 11Zm0 0c8 0 12-5 12-11-8 0-12 5-12 11Z" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
}

function statusClass(status) {
    if (status === 'live') return 'status-live';
    if (status === 'ended') return 'status-ended';
    return 'status-waiting';
}

function firstProject() {
    return state.projects?.[0] || null;
}

function getProjectFromPath() {
    const match = currentPath().match(/^\/projekt\/(\d+)/);
    if (!match) return null;
    return state.projects?.find((project) => String(project.id) === match[1]) || firstProject();
}

function getAnnouncementFromPath() {
    const slug = decodeURIComponent(currentPath().replace('/ogloszenia/', ''));
    return state.announcements?.find((item) => item.slug === slug) || state.announcements?.[0] || null;
}

function getInfoPageFromPath() {
    const slug = decodeURIComponent(currentPath().replace('/informacje/', ''));
    return state.pages?.find((item) => item.slug === slug) || state.pages?.[0] || null;
}

function layout(content) {
    const c = copy();
    return `
        <a class="skip-link" href="#main">Przejdź do treści</a>
        <div class="topbar" aria-label="Narzędzia dostępności">
            <div class="topbar-inner">
                <ul class="utility-list">
                    <li><a class="utility-link" href="#main">Treść</a></li>
                    <li><button class="contrast-button" type="button" data-contrast-toggle aria-pressed="${document.body.dataset.contrast === 'high'}">Kontrast</button></li>
                    <li><button class="font-button" type="button" data-font-toggle aria-pressed="${document.body.dataset.font === 'large'}">A+</button></li>
                </ul>
                <div class="aioa-topbar-slot" data-aioa-slot aria-label="Widget dostępności"></div>
                <ul class="lang-list" aria-label="Wybór języka">
                    ${['pl', 'uk', 'en', 'de'].map((lang) => `
                        <li><button class="lang-button" type="button" data-lang="${lang}" aria-pressed="${document.documentElement.lang === lang}">${langLabel(lang)}</button></li>
                    `).join('')}
                </ul>
            </div>
        </div>
        <header class="site-header">
            <a class="brand" href="${href('home')}" data-spa-link aria-label="${escapeHtml(state.app?.title)}">
                <span class="brand-mark" aria-hidden="true">M</span>
                <span class="brand-text">
                    <span class="brand-city">Miasto</span>
                    <span class="brand-sub">miejsce na logotyp i herb</span>
                </span>
            </a>
            <nav aria-label="Główna nawigacja">
                <ul class="nav-list">
                    <li><a class="nav-link" href="${href('home')}" data-spa-link ${active('/') ? 'aria-current="page"' : ''} data-i18n="navHome">${c.navHome}</a></li>
                    <li><a class="nav-link" href="${href('projects')}" data-spa-link ${active('/projekty') || active('/projekt') ? 'aria-current="page"' : ''} data-i18n="navProjects">${c.navProjects}</a></li>
                    ${renderInfoDropdown(c.navInfo)}
                    <li><a class="btn btn-primary" href="${href('submit')}" data-spa-link data-i18n="navSubmit">${c.navSubmit}</a></li>
                </ul>
            </nav>
        </header>
        <main id="main" class="page-shell">
            ${state.app?.flash ? `<p class="panel">${escapeHtml(state.app.flash)}</p>` : ''}
            ${content}
        </main>
        <footer class="site-footer">
            <div class="footer-inner">
                <p>Budżet Obywatelski Miasta. Prototyp publicznej części serwisu dla mieszkańca.</p>
                <a href="/informacje/o-budzecie" data-spa-link>Deklaracja dostępności</a>
            </div>
        </footer>
        <div class="toast" data-toast role="status" aria-live="polite"></div>
    `;
}

function homeView() {
    const announcement = state.announcements?.[0];
    return `
        <section class="campaign-hero" aria-labelledby="hero-title">
            <div class="hero-panel">
                <p class="eyebrow">${escapeHtml(state.edition?.name || 'Edycja lokalna')}</p>
                <h1 id="hero-title">Budżet Obywatelski w Twoim Mieście</h1>
                <p class="lead">Zgłoś projekt, sprawdź listę zadań i zagłosuj na pomysły, które zmienią najbliższą okolicę. Serwis jest gotowy do podmiany logo, zdjęć i lokalnych danych miasta.</p>
                <div class="hero-actions" aria-label="Najważniejsze akcje">
                    <a class="btn btn-primary" href="${href('submit')}" data-spa-link>Dodaj projekt</a>
                    <a class="btn btn-secondary" href="${href('projects')}" data-spa-link>Lista projektów</a>
                    <a class="btn btn-secondary" href="/informacje/o-budzecie" data-spa-link>Jak to działa?</a>
                </div>
                <div class="countdown-card" aria-label="Aktualny etap">
                    <span><strong>${escapeHtml(state.stats?.projects ?? 0)}</strong> projektów</span>
                    <span><strong>${escapeHtml(state.stats?.picked ?? 0)}</strong> do głosowania</span>
                    <span><strong>${escapeHtml(state.edition?.stateDate || 'Wkrótce')}</strong> termin</span>
                </div>
            </div>
        </section>

        <article class="news-strip" aria-labelledby="news-title">
            <div class="news-thumb" aria-hidden="true">BO<br>${escapeHtml(new Date().getFullYear())}</div>
            <div>
                <span class="notice-date">${announcement ? escapeHtml(announcement.date) : 'Aktualności'}</span>
                <h3 id="news-title">${escapeHtml(announcement?.title || 'Rusza nabór projektów mieszkańców')}</h3>
                <p>${escapeHtml(announcement?.lead || 'Przygotuj opis, lokalizację i szacunkowy koszt. W razie wątpliwości skorzystaj z dyżurów konsultacyjnych lub punktu pomocy w urzędzie.')}</p>
                <a class="btn btn-primary" href="${announcement?.url || href('submit')}" data-spa-link>${announcement ? 'Czytaj ogłoszenie' : 'Złóż projekt'}</a>
            </div>
        </article>

        <section class="knowledge-section" aria-labelledby="knowledge-title">
            <div class="knowledge-grid">
                <article class="knowledge-card">
                    <p class="eyebrow">Co warto wiedzieć</p>
                    <h2 id="knowledge-title">Budżet krok po kroku</h2>
                    <div class="knowledge-list">
                        ${['Mieszkańcy zgłaszają pomysły', 'Miasto sprawdza wykonalność', 'O realizacji decyduje głosowanie'].map((title, index) => `
                            <div class="knowledge-item">
                                <span class="icon-bubble" aria-hidden="true">${index + 1}</span>
                                <div>
                                    <h3>${title}</h3>
                                    <p>${index === 0 ? 'Projekt opisuje potrzebę, miejsce, odbiorców i szacunkowy koszt.' : index === 1 ? 'Weryfikacja porządkuje listę i wskazuje projekty możliwe do wykonania.' : 'Lista do głosowania trafia do mieszkańców w jednym, czytelnym katalogu.'}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </article>
                <aside class="city-personality" aria-label="Miejsce na charakter miasta">
                    <div class="city-badge">Logo miasta</div>
                    <h3>Tu wstawiasz herb, zdjęcie, hasło i lokalne komunikaty</h3>
                    <p>Ten blok nadaje charakter konkretnej gminie bez wiązania produktu z jednym miastem.</p>
                </aside>
            </div>
        </section>

        <section class="action-band" aria-labelledby="submit-title">
            <div class="action-band-inner">
                <div>
                    <p class="eyebrow">Jak zgłosić projekt?</p>
                    <h2 id="submit-title">Wymyśl zadanie dla swojej okolicy</h2>
                    <p class="lead">Opisz problem, wskaż miejsce, dodaj koszt i wyślij projekt do weryfikacji.</p>
                    <div class="action-row">
                        <a class="btn btn-primary" href="${href('submit')}" data-spa-link>Zabierz głos</a>
                        <a class="btn btn-secondary" href="/informacje/harmonogram" data-spa-link>Sprawdź zasady</a>
                    </div>
                </div>
                <div class="submit-illustration" aria-hidden="true">
                    <div class="paper-line"></div><div class="paper-line"></div><div class="paper-line"></div><div class="paper-line"></div>
                    <h3>Formularz projektu</h3>
                </div>
            </div>
        </section>

        <section class="section" aria-labelledby="cost-title">
            <div class="section-head centered">
                <p class="eyebrow">Tematy projektów</p>
                <h2 id="cost-title">Projekty są konkretne i zrozumiałe</h2>
                <p class="lead">Zieleń, mobilność, dostępność, sport, kultura i sąsiedzkie spotkania mają własny rytm wizualny.</p>
            </div>
            <div class="cost-grid">
                <div class="cost-visual">
                    <img src="/images/public-spa/civic-projects-illustration.png" alt="Mieszkańcy planujący projekty miejskie w przestrzeni publicznej">
                </div>
                <div class="cost-callouts">
                    <article class="callout"><h3>Zieleń i odpoczynek</h3><p>Parki kieszonkowe, ławki, drzewa, retencja i cień w upalne dni.</p></article>
                    <article class="callout"><h3>Bezpieczna mobilność</h3><p>Przejścia, drogi rowerowe, stojaki i doświetlenie tras do szkół.</p></article>
                    <article class="callout"><h3>Dostępność</h3><p>Podjazdy, czytelna informacja i udogodnienia dla wszystkich mieszkańców.</p></article>
                </div>
            </div>
        </section>

        ${priceGuide()}
        ${mapBand()}
        ${scheduleBand()}
        ${finalCta()}
    `;
}

function priceGuide() {
    const fallbackItems = [
        { label: 'Stojak rowerowy', priceRange: '700-1 500 zł' },
        { label: 'Ławka z montażem', priceRange: '2-6 tys. zł' },
        { label: 'Drzewo z pielęgnacją', priceRange: '1-3 tys. zł' },
        { label: 'Warsztaty sąsiedzkie', priceRange: '5-20 tys. zł' },
        { label: 'Doświetlenie przejścia', priceRange: '30-90 tys. zł' },
    ];
    const items = state.costGuideItems?.length ? state.costGuideItems : fallbackItems;

    return `
        <section class="section price-guide" aria-labelledby="price-title">
            <div class="price-board">
                <div class="price-copy">
                    <p class="eyebrow">Cennik inspiracji</p>
                    <h2 id="price-title">Ile kosztuje miasto?</h2>
                    <p class="lead">Mieszkaniec nie musi znać kosztorysów, ale dobry punkt odniesienia pomaga zgłosić realny projekt. Ten blok pokazuje przykładowe widełki cenowe do podmiany na lokalny katalog.</p>
                    <a class="btn btn-primary" href="/informacje/katalog-inspiracji" data-spa-link>Otwórz pełny katalog</a>
                </div>
                <aside class="price-receipt" aria-label="Przykładowy cennik miejski">
                    <div class="receipt-head">
                        <span>BO / kosztorys</span>
                        <strong>orientacyjnie</strong>
                    </div>
                    <ul class="price-list">
                        ${items.map((item) => `
                            <li>
                                <span>${escapeHtml(item.label)}</span>
                                <strong>${escapeHtml(item.priceRange)}</strong>
                            </li>
                        `).join('')}
                    </ul>
                    <p>Kwoty są pokazowe. Ostateczna wycena powstaje podczas weryfikacji projektu.</p>
                </aside>
            </div>
        </section>
    `;
}

function mapBand() {
    return `
        <section class="section" aria-labelledby="map-title">
            <div class="map-band">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Mapa realizacji</p>
                        <h2 id="map-title">Pokaż projekty w przestrzeni miasta</h2>
                    </div>
                    <p class="lead">Schematyczny widok lokalizacji można później podmienić na integrację GIS lub prostą mapę z pinezkami.</p>
                </div>
                <div class="map-graphic" role="img" aria-label="Schematyczna mapa miasta z trzema pinezkami projektów">
                    <span class="pin"></span><span class="pin"></span><span class="pin"></span>
                </div>
            </div>
        </section>
    `;
}

function scheduleBand() {
    return `
        <section class="schedule-band" aria-labelledby="process-title">
            <p class="eyebrow">Harmonogram</p>
            <h2 id="process-title">Od pomysłu do wyników</h2>
            <p class="lead">Kolorowy pas etapów od razu pokazuje, gdzie jesteśmy w procesie.</p>
            <div class="schedule-steps">
                ${(state.timeline || []).map((step) => `
                    <article class="stage ${step.active ? 'is-active' : ''}" ${step.active ? 'aria-current="step"' : ''}>
                        <span class="stage-index">${String(step.index).padStart(2, '0')}</span>
                        ${step.active ? '<span class="stage-status">Aktywny etap</span>' : ''}
                        <h3>${escapeHtml(step.label)}</h3>
                        <p>${escapeHtml(step.date)}</p>
                    </article>
                `).join('')}
            </div>
        </section>
    `;
}

function finalCta() {
    return `
        <section class="section" aria-labelledby="final-title">
            <div class="final-cta">
                <p class="eyebrow">Zgłoś zadanie</p>
                <h2 id="final-title">Masz pomysł? Zacznij od formularza.</h2>
                <p class="lead">Najkrótsza ścieżka mieszkańca powinna kończyć się konkretną akcją, nie kolejną ścianą tekstu.</p>
                <a class="btn btn-primary" href="${href('submit')}" data-spa-link>Zaproponuj projekt</a>
            </div>
        </section>
    `;
}

function projectsView() {
    const selectedArea = queryValue('area_id');
    const selectedCategory = queryValue('category_id');
    const selectedQuery = queryValue('q');
    return `
        <section class="section" aria-labelledby="projects-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Katalog projektów</p>
                    <h1 id="projects-title">Wybierz projekt do sprawdzenia.</h1>
                </div>
                <p class="lead">Lista pokazuje kategorie, statusy, szacunkowe koszty i obszary miasta. Filtry działają lokalnie, bez przeładowania strony.</p>
            </div>
            <div class="project-layout">
                <aside class="filter-panel" aria-labelledby="filter-title">
                    <h2 id="filter-title">Filtry</h2>
                    <form data-project-filters>
                        <div class="field">
                            <label for="query">Szukaj</label>
                            <input id="query" name="query" type="search" value="${escapeHtml(selectedQuery)}" data-i18n-placeholder="searchPlaceholder" placeholder="${copy().searchPlaceholder}">
                        </div>
                        <div class="field">
                            <label for="area">Obszar</label>
                            <select id="area" name="area">
                                <option value="">Wszystkie</option>
                                ${(state.areas || []).map((area) => `<option value="${area.id}" ${String(selectedArea) === String(area.id) ? 'selected' : ''}>${escapeHtml(area.name)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="field">
                            <label for="category">Kategoria</label>
                            <select id="category" name="category">
                                <option value="">Wszystkie</option>
                                ${(state.categories || []).map((category) => `<option value="${category.id}" ${String(selectedCategory) === String(category.id) ? 'selected' : ''}>${escapeHtml(category.name)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">Wszystkie</option>
                                <option value="live">Do głosowania</option>
                                <option value="waiting">W weryfikacji</option>
                                <option value="ended">Wybrany do realizacji</option>
                            </select>
                        </div>
                        <button class="btn btn-secondary" type="reset">Wyczyść filtry</button>
                    </form>
                    <p class="form-hint"><span data-result-count>0</span> wyników</p>
                </aside>
                <section aria-label="Wyniki listy projektów">
                    <p class="panel" data-empty hidden>${copy().noResults}</p>
                    <div class="project-grid">
                        ${(state.projects || []).map(projectCard).join('') || emptyProjects()}
                    </div>
                </section>
            </div>
        </section>
        <section id="submit" class="section">
            <div class="section-head">
                <h2>Zgłoś projekt</h2>
                <p class="lead">Pełny formularz jest dostępny jako osobny widok SPA i korzysta z istniejącej walidacji Laravel.</p>
            </div>
            <a class="btn btn-primary" href="${href('submit')}" data-spa-link>Przejdź do formularza</a>
        </section>
    `;
}

function projectCard(project) {
    const search = [project.title, project.area, project.category, project.description, project.statusLabel].join(' ');
    return `
        <article class="project-card" data-project-card data-area="${escapeHtml(project.areaId)}" data-category="${escapeHtml(project.categoryId)}" data-status="${escapeHtml(project.status)}" data-search="${escapeHtml(search)}">
            <div class="project-card-head">
                <span class="project-icon" aria-hidden="true">${projectIcon(project.category)}</span>
                <div>
                    <span class="status ${statusClass(project.status)}">${escapeHtml(project.statusLabel)}</span>
                    <h3>${escapeHtml(project.title)}</h3>
                </div>
            </div>
            <p>${escapeHtml(project.description)}</p>
            <p class="project-meta">${escapeHtml(project.category)} · ${escapeHtml(project.area)} · ${escapeHtml(project.costLabel)}</p>
            <div class="project-actions">
                <a class="btn btn-primary" href="${escapeHtml(project.url)}" data-spa-link>Szczegóły</a>
                <button class="btn btn-secondary" type="button" data-save-action>Obserwuj</button>
            </div>
        </article>
    `;
}

function emptyProjects() {
    return '<p class="panel">Nie ma jeszcze publicznych projektów. Po publikacji pojawią się w tym katalogu.</p>';
}

function projectDetailView() {
    const project = getProjectFromPath();
    if (!project) return projectsView();

    return `
        <section class="section detail-hero" aria-labelledby="project-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Projekt ${project.number ? `nr ${escapeHtml(project.number)}` : 'mieszkańców'}</p>
                    <h1 id="project-title">${escapeHtml(project.title)}</h1>
                </div>
                <a class="btn btn-secondary" href="${href('projects')}" data-spa-link>Wróć do listy</a>
            </div>
            <div class="detail-layout">
                <article class="panel detail-body">
                    <span class="status ${statusClass(project.status)}">${escapeHtml(project.statusLabel)}</span>
                    <h2>Opis projektu</h2>
                    <p>${escapeHtml(project.fullDescription || project.description)}</p>
                    <h2>Cel</h2>
                    <p>${escapeHtml(project.goal || 'Cel projektu zostanie pokazany po uzupełnieniu danych.')}</p>
                    <h2>Lokalizacja</h2>
                    <p>${escapeHtml(project.localization || project.area)}</p>
                    ${project.files?.length ? `
                        <h2>Załączniki publiczne</h2>
                        <ul class="screen-list">
                            ${project.files.map((file) => `<li><a href="${escapeHtml(file.url || '#')}">${escapeHtml(file.name)}</a></li>`).join('')}
                        </ul>
                    ` : ''}
                    ${project.comments?.length ? `
                        <h2>Komentarze</h2>
                        <ul class="screen-list">
                            ${project.comments.map((comment) => `
                                <li>
                                    ${escapeHtml(comment.content)}${comment.hidden ? ' <strong>Ukryty</strong>' : ''}
                                    ${comment.canManage ? `
                                        <form method="post" action="${escapeHtml(project.url)}/komentarze/${escapeHtml(comment.id)}">
                                            <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                                            <input type="hidden" name="_method" value="PUT">
                                            <div class="field">
                                                <label for="comment_${escapeHtml(comment.id)}">Edytuj komentarz</label>
                                                <textarea id="comment_${escapeHtml(comment.id)}" name="content" maxlength="200">${escapeHtml(comment.content)}</textarea>
                                            </div>
                                            <button class="btn btn-secondary" type="submit">Zapisz komentarz</button>
                                        </form>
                                        <form method="post" action="${escapeHtml(project.url)}/komentarze/${escapeHtml(comment.id)}/widocznosc">
                                            <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                                            <input type="hidden" name="_method" value="PATCH">
                                            <button class="btn btn-secondary" type="submit">${comment.hidden ? 'Przywróć' : 'Ukryj'}</button>
                                        </form>
                                    ` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    ` : ''}
                    ${state.app?.authenticated ? `
                        <h2>Dodaj komentarz</h2>
                        <form method="post" action="${escapeHtml(project.url)}/komentarze">
                            <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                            <div class="field">
                                <label for="new_comment">Treść komentarza</label>
                                <textarea id="new_comment" name="content" required maxlength="200"></textarea>
                            </div>
                            <button class="btn btn-primary" type="submit">Dodaj komentarz</button>
                        </form>
                    ` : ''}
                </article>
                <aside class="filter-panel">
                    <h2>Dane projektu</h2>
                    <ul class="screen-list">
                        <li><strong>Obszar:</strong> ${escapeHtml(project.area)}</li>
                        <li><strong>Kategoria:</strong> ${escapeHtml(project.category)}</li>
                        <li><strong>Koszt:</strong> ${escapeHtml(project.costLabel)}</li>
                        <li><strong>Status:</strong> ${escapeHtml(project.statusLabel)}</li>
                    </ul>
                    <a class="btn btn-primary" href="${href('voting')}" data-spa-link>Przejdź do głosowania</a>
                </aside>
            </div>
        </section>
    `;
}

function submitView() {
    const errors = Object.values(state.app?.errors || {}).flat();
    const old = state.app?.old || {};
    const editionId = old.budget_edition_id || state.edition?.id || '';
    return `
        <section class="section" aria-labelledby="submit-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Formularz mieszkańca</p>
                    <h1 id="submit-title">Zgłoś projekt</h1>
                </div>
                <p class="lead">Ten widok jest SPA, ale wysyłka formularza korzysta z istniejącego endpointu Laravel, walidacji i logiki domenowej.</p>
            </div>
            ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}
            <form class="form-panel" method="post" action="${href('projectStore')}" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <input type="hidden" name="budget_edition_id" value="${escapeHtml(editionId)}">
                <input type="hidden" name="map_data" value='{"type":"FeatureCollection","features":[]}'>

                <div class="form-grid">
                    <div class="field">
                        <label for="author_first_name">Imię autora</label>
                        <input id="author_first_name" name="author_first_name" required maxlength="127" value="${escapeHtml(old.author_first_name)}">
                    </div>
                    <div class="field">
                        <label for="author_last_name">Nazwisko autora</label>
                        <input id="author_last_name" name="author_last_name" required maxlength="127" value="${escapeHtml(old.author_last_name)}">
                    </div>
                    <div class="field">
                        <label for="author_email">E-mail</label>
                        <input id="author_email" name="author_email" type="email" required maxlength="255" value="${escapeHtml(old.author_email)}">
                    </div>
                    <div class="field">
                        <label for="author_phone">Telefon</label>
                        <input id="author_phone" name="author_phone" maxlength="30" value="${escapeHtml(old.author_phone)}">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="author_street">Ulica</label>
                        <input id="author_street" name="author_street" maxlength="127" value="${escapeHtml(old.author_street)}">
                    </div>
                    <div class="field">
                        <label for="author_house_no">Nr domu</label>
                        <input id="author_house_no" name="author_house_no" maxlength="20" value="${escapeHtml(old.author_house_no)}">
                    </div>
                    <div class="field">
                        <label for="author_flat_no">Nr lokalu</label>
                        <input id="author_flat_no" name="author_flat_no" maxlength="20" value="${escapeHtml(old.author_flat_no)}">
                    </div>
                    <div class="field">
                        <label for="author_post_code">Kod pocztowy</label>
                        <input id="author_post_code" name="author_post_code" maxlength="6" value="${escapeHtml(old.author_post_code)}">
                    </div>
                </div>

                <div class="field">
                    <label for="author_city">Miejscowość</label>
                    <input id="author_city" name="author_city" maxlength="127" value="${escapeHtml(old.author_city)}">
                </div>

                <div class="form-grid">
                    <label class="check-row"><input name="author_email_agree" type="checkbox" value="1" checked> Publikowana forma kontaktu: e-mail.</label>
                    <label class="check-row"><input name="author_phone_agree" type="checkbox" value="1"> Publikowana forma kontaktu: telefon.</label>
                </div>
                <input type="hidden" name="contact_with" value="1">
                <label class="check-row"><input name="author_read_confirm" type="checkbox" value="1" required> ${escapeHtml(state.legacyText?.regulation_confirmation || 'Potwierdzam zapoznanie się z regulaminem.')}</label>
                <label class="check-row"><input name="author_personal_data_agree" type="checkbox" value="1"> ${escapeHtml(state.legacyText?.evaluation_consent_checkbox || 'Wyrażam zgodę na przetwarzanie danych do weryfikacji projektu.')}</label>

                <div class="field">
                    <label for="title">Tytuł projektu</label>
                    <input id="title" name="title" required maxlength="600" value="${escapeHtml(old.title)}" placeholder="np. Zielony skwer przy bibliotece">
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="project_area_id">Obszar</label>
                        <select id="project_area_id" name="project_area_id" required>
                            ${(state.areas || []).map((area) => `<option value="${area.id}" ${String(old.project_area_id || '') === String(area.id) ? 'selected' : ''}>${escapeHtml(area.name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="field">
                        <label for="category_id">Kategoria</label>
                        <select id="category_id" name="category_id" required>
                            ${(state.categories || []).map((category) => `<option value="${category.id}" ${String(old.category_id || '') === String(category.id) ? 'selected' : ''}>${escapeHtml(category.name)}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="local">Typ projektu</label>
                    <select id="local" name="local" required>
                        <option value="1">Projekt lokalny</option>
                        <option value="2">Projekt Zielonego BO</option>
                    </select>
                </div>
                <div class="field">
                    <label for="short_description">Skrócony opis</label>
                    <textarea id="short_description" name="short_description" maxlength="700">${escapeHtml(old.short_description)}</textarea>
                </div>
                ${textareaField('localization', 'Lokalizacja', true, old.localization)}
                <div class="field"><label for="address">Adres</label><input id="address" name="address" maxlength="300" value="${escapeHtml(old.address)}"></div>
                ${textareaField('plot', 'Działka', false, old.plot)}
                ${textareaField('description', 'Opis', true, old.description)}
                ${textareaField('goal', 'Cel', true, old.goal)}
                ${textareaField('argumentation', 'Uzasadnienie', true, old.argumentation)}
                ${textareaField('availability', 'Dostępność', true, old.availability)}
                ${textareaField('recipients', 'Odbiorcy', true, old.recipients)}
                ${textareaField('free_of_charge', 'Bezpłatność', true, old.free_of_charge)}
                ${textareaField('additional_cost', 'Koszty utrzymania w kolejnych latach', false, old.additional_cost)}

                <div class="form-grid">
                    <div class="field">
                        <label for="cost_description">Składowa kosztów</label>
                        <input id="cost_description" name="cost_items[0][description]" required maxlength="1000" value="${escapeHtml(old.cost_description)}">
                    </div>
                    <div class="field">
                        <label for="cost_amount">Koszt brutto</label>
                        <input id="cost_amount" name="cost_items[0][amount]" required type="number" min="0" step="0.01" value="${escapeHtml(old.cost_amount)}">
                    </div>
                </div>

                <label class="check-row"><input name="show_task_coauthors" type="checkbox" value="1" checked> Informacje o współautorze mają być wyświetlane.</label>
                <label class="check-row"><input name="consent_to_change" type="checkbox" value="1"> ${escapeHtml(state.legacyText?.consent_to_change || 'Wyrażam zgodę na ewentualne zmiany projektu po konsultacji.')}</label>
                <label class="check-row"><input name="attachments_anonymized" type="checkbox" value="1" required> ${escapeHtml(state.legacyText?.attachments_anonymized || 'Załączniki nie zawierają danych wymagających ukrycia.')}</label>
                <label class="check-row"><input name="support_list" type="checkbox" value="1" required> ${escapeHtml(state.legacyText?.support_list || 'Dołączam listę poparcia.')}</label>

                <div class="field">
                    <label for="support_list_file">Plik listy poparcia</label>
                    <input id="support_list_file" name="support_list_file" type="file" required>
                </div>
                <div class="form-grid">
                    <div class="field"><label for="owner_agreement_files">Zgody właściciela</label><input id="owner_agreement_files" name="owner_agreement_files[]" type="file" multiple></div>
                    <div class="field"><label for="map_files">Załączniki mapy</label><input id="map_files" name="map_files[]" type="file" multiple></div>
                    <div class="field"><label for="parent_agreement_files">Zgody rodzica lub opiekuna</label><input id="parent_agreement_files" name="parent_agreement_files[]" type="file" multiple></div>
                    <div class="field"><label for="attachment_files">Pozostałe załączniki</label><input id="attachment_files" name="attachment_files[]" type="file" multiple></div>
                </div>
                <button class="btn btn-primary" type="submit">Złóż projekt</button>
            </form>
        </section>
    `;
}

function textareaField(name, label, required, value) {
    return `
        <div class="field">
            <label for="${name}">${label}</label>
            <textarea id="${name}" name="${name}" ${required ? 'required' : ''}>${escapeHtml(value)}</textarea>
        </div>
    `;
}

function announcementsView() {
    return `
        <section class="section" aria-labelledby="announcements-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Komunikaty miasta</p>
                    <h1 id="announcements-title">Ogłoszenia</h1>
                </div>
                <p class="lead">Najważniejsze informacje o terminach, listach projektów, głosowaniu i wynikach.</p>
            </div>
            <div class="project-grid">
                ${(state.announcements || []).map((item) => `
                    <article class="project-card">
                        <p class="notice-date">${escapeHtml(item.date)}</p>
                        <h3>${escapeHtml(item.title)}</h3>
                        <p>${escapeHtml(item.lead)}</p>
                        <a class="btn btn-primary" href="${escapeHtml(item.url)}" data-spa-link>Czytaj</a>
                    </article>
                `).join('') || '<p class="panel">Brak opublikowanych ogłoszeń.</p>'}
            </div>
        </section>
    `;
}

function announcementDetailView() {
    const announcement = getAnnouncementFromPath();
    if (!announcement) return announcementsView();
    return `
        <section class="section content-page" aria-labelledby="announcement-title">
            <p class="eyebrow">${escapeHtml(announcement.date)}</p>
            <h1 id="announcement-title">${escapeHtml(announcement.title)}</h1>
            <div class="panel content-body">${html(announcement.body || `<p>${escapeHtml(announcement.lead)}</p>`)}</div>
            <a class="btn btn-secondary" href="${href('announcements')}" data-spa-link>Wróć do ogłoszeń</a>
        </section>
    `;
}

function infoView() {
    const page = getInfoPageFromPath();
    return `
        <section class="section content-page" aria-labelledby="info-title">
            <p class="eyebrow">Informacje</p>
            <h1 id="info-title">${escapeHtml(page?.title || 'O budżecie obywatelskim')}</h1>
            <div class="panel content-body">${page?.body ? html(page.body) : '<p>Tu pojawią się lokalne zasady, dokumenty, harmonogram i najważniejsze informacje dla mieszkańców.</p>'}</div>
            ${scheduleBand()}
        </section>
    `;
}

function mapView() {
    return `
        <section class="section" aria-labelledby="map-page-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Mapa projektów</p>
                    <h1 id="map-page-title">Projekty w przestrzeni miasta</h1>
                </div>
                <p class="lead">Schematyczny widok pokazuje, że docelowo projekty mogą być filtrowane również po lokalizacji.</p>
            </div>
            ${mapBand()}
            ${state.mapPoints?.length ? `
                <div class="panel">
                    <h2>Współrzędne projektów</h2>
                    <ul class="screen-list">
                        ${state.mapPoints.map((point) => `<li>${escapeHtml(point.title)} · ${escapeHtml(point.coords)}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
            <div class="project-grid">
                ${(state.projects || []).slice(0, 6).map(projectCard).join('') || emptyProjects()}
            </div>
        </section>
    `;
}

function votingView() {
    const localProjects = state.voting?.localProjects || [];
    const citywideProjects = state.voting?.citywideProjects || [];
    return `
        <section class="section" aria-labelledby="vote-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Głosowanie</p>
                    <h1 id="vote-title">Wybierz projekty mieszkańców</h1>
                </div>
                <p class="lead">Wypełnij dane, odbierz kod SMS i oddaj głos przez istniejące endpointy backendu.</p>
            </div>
            <div class="project-layout">
                <aside class="filter-panel">
                    <h2>Kod SMS</h2>
                    <form method="post" action="${href('voteToken')}">
                        <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                        ${voterIdentityFields('token')}
                        <button class="btn btn-primary" type="submit">Wyślij kod</button>
                    </form>
                </aside>
                <section>
                    <form class="form-panel" method="post" action="${href('voteCast')}">
                        <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                        <input type="hidden" name="budget_edition_id" value="${escapeHtml(state.voting?.edition_id || '')}">
                        ${voterIdentityFields('vote')}
                        <div class="field">
                            <label for="sms_token">Kod SMS</label>
                            <input id="sms_token" name="sms_token" required maxlength="6">
                        </div>
                        <div class="field">
                            <label for="local_project_id">Projekt lokalny</label>
                            <select id="local_project_id" name="local_project_id">
                                <option value="">Bez głosu lokalnego</option>
                                ${localProjects.map((project) => `<option value="${project.id}">${escapeHtml(project.number || '')}. ${escapeHtml(project.title)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="field">
                            <label for="citywide_project_id">Projekt ogólnomiejski</label>
                            <select id="citywide_project_id" name="citywide_project_id">
                                <option value="">Bez głosu ogólnomiejskiego</option>
                                ${citywideProjects.map((project) => `<option value="${project.id}">${escapeHtml(project.number || '')}. ${escapeHtml(project.title)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="field">
                            <label for="citizen_confirm">Oświadczenie</label>
                            <select id="citizen_confirm" name="citizen_confirm">
                                <option value="1">Jestem w rejestrze mieszkańców</option>
                                <option value="2">Mieszkam w mieście</option>
                                <option value="3">Uczę się, studiuję albo pracuję w mieście</option>
                            </select>
                        </div>
                        <label class="check-row"><input name="confirm_missing_category" type="checkbox" value="1"> Potwierdzam świadomy brak głosu w jednej kategorii.</label>
                        <div class="field">
                            <label for="parent_name">Imię i nazwisko rodzica/opiekuna</label>
                            <input id="parent_name" name="parent_name">
                        </div>
                        <label class="check-row"><input name="parent_confirm" type="checkbox" value="1"> Rodzic/opiekun potwierdza udział osoby niepełnoletniej.</label>
                        <button class="btn btn-primary" type="submit">Oddaj głos</button>
                    </form>
                </section>
            </div>
            <div class="project-grid">
                ${[...localProjects, ...citywideProjects].map((project) => `
                    <article class="project-card">
                        <span class="status status-live">${escapeHtml(project.area || 'Projekt')}</span>
                        <h3>${escapeHtml(project.title)}</h3>
                        <p class="project-meta">Nr ${escapeHtml(project.number || '')}</p>
                    </article>
                `).join('') || '<p class="panel">Lista projektów do głosowania nie jest jeszcze opublikowana.</p>'}
            </div>
        </section>
    `;
}

function voterIdentityFields(prefix) {
    return `
        <div class="field">
            <label for="${prefix}_pesel">PESEL</label>
            <input id="${prefix}_pesel" name="pesel" required maxlength="11">
        </div>
        <div class="field">
            <label for="${prefix}_first_name">Imię</label>
            <input id="${prefix}_first_name" name="first_name" required maxlength="127">
        </div>
        <div class="field">
            <label for="${prefix}_last_name">Nazwisko</label>
            <input id="${prefix}_last_name" name="last_name" required maxlength="127">
        </div>
        <div class="field">
            <label for="${prefix}_mother_last_name">Nazwisko rodowe matki</label>
            <input id="${prefix}_mother_last_name" name="mother_last_name" required maxlength="127">
        </div>
        <div class="field">
            <label for="${prefix}_phone">Telefon</label>
            <input id="${prefix}_phone" name="phone" required maxlength="30">
        </div>
    `;
}

function resultsView() {
    const pointsByProject = new Map((state.results?.totals || []).map((total) => [String(total.project_id), total.points]));
    return `
        <section class="section" aria-labelledby="results-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Wyniki</p>
                    <h1 id="results-title">Wyniki głosowania</h1>
                </div>
                <p class="lead">Po publikacji wyników ta strona pokaże ranking projektów, liczbę głosów i projekty wybrane do realizacji.</p>
            </div>
            ${!state.results?.published ? `<p class="panel">${escapeHtml(state.results?.message || 'Wyniki nie zostały jeszcze opublikowane.')}</p>` : ''}
            <div class="project-grid">
                ${state.results?.published
                    ? ((state.projects || []).map((project) => `${projectCard(project)}<p class="project-meta">Liczba punktów: ${escapeHtml(pointsByProject.get(String(project.id)) ?? 0)}</p>`).join('') || '<p class="panel">Brak opublikowanych wyników.</p>')
                    : ''}
            </div>
        </section>
    `;
}

function renderRoute() {
    const path = currentPath();
    if (path === '/') return homeView();
    if (path === '/projekty') return projectsView();
    if (path.startsWith('/projekt/')) return projectDetailView();
    if (path === '/projekty/zglos') return submitView();
    if (path === '/projekty-mapa') return mapView();
    if (path === '/ogloszenia') return announcementsView();
    if (path.startsWith('/ogloszenia/')) return announcementDetailView();
    if (path.startsWith('/informacje/')) return infoView();
    if (path === '/glosowanie') return votingView();
    if (path === '/wyniki') return resultsView();
    if (path === '/raporty-publiczne') return infoView();
    return homeView();
}

function showToast(message) {
    const toast = document.querySelector('[data-toast]');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => toast.classList.remove('is-visible'), 2200);
}

function bindActions() {
    document.querySelectorAll('[data-spa-link]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.origin);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            window.history.pushState({}, '', url.pathname + url.search + url.hash);
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    cleanupNavDropdown?.();
    cleanupNavDropdown = null;

    const dropdown = document.querySelector('[data-nav-dropdown]');
    const dropdownItem = dropdown?.closest('.nav-item-dropdown');
    const closeDropdown = () => {
        dropdownItem?.classList.remove('is-open');
        dropdown?.setAttribute('aria-expanded', 'false');
    };
    const toggleDropdown = (event) => {
        event.stopPropagation();
        const isOpen = dropdownItem?.classList.toggle('is-open') || false;
        dropdown.setAttribute('aria-expanded', String(isOpen));
    };
    const closeOnOutsideClick = (event) => {
        if (!dropdownItem || dropdownItem.contains(event.target)) return;
        closeDropdown();
    };
    const closeOnEscape = (event) => {
        if (event.key !== 'Escape') return;
        closeDropdown();
    };

    if (dropdown) {
        dropdown.addEventListener('click', toggleDropdown);
        document.addEventListener('click', closeOnOutsideClick);
        document.addEventListener('keydown', closeOnEscape);
        cleanupNavDropdown = () => {
            dropdown.removeEventListener('click', toggleDropdown);
            document.removeEventListener('click', closeOnOutsideClick);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }

    document.querySelectorAll('[data-lang]').forEach((button) => {
        button.addEventListener('click', () => {
            document.documentElement.lang = button.dataset.lang || 'pl';
            window.localStorage.setItem('bo:lang', document.documentElement.lang);
            render();
            showToast(copy().languageChanged);
        });
    });

    const contrast = document.querySelector('[data-contrast-toggle]');
    contrast?.addEventListener('click', () => {
        const next = document.body.dataset.contrast === 'high' ? 'base' : 'high';
        document.body.dataset.contrast = next;
        showToast(next === 'high' ? copy().contrastOn : copy().contrastOff);
        contrast.setAttribute('aria-pressed', String(next === 'high'));
    });

    const font = document.querySelector('[data-font-toggle]');
    font?.addEventListener('click', () => {
        const next = document.body.dataset.font === 'large' ? 'base' : 'large';
        document.body.dataset.font = next;
        showToast(next === 'large' ? copy().fontLarge : copy().fontBase);
        font.setAttribute('aria-pressed', String(next === 'large'));
    });

    document.querySelectorAll('[data-save-action]').forEach((button) => {
        button.addEventListener('click', () => {
            button.setAttribute('aria-pressed', 'true');
            showToast(copy().saved);
        });
    });

    const form = document.querySelector('[data-project-filters]');
    if (form) {
        const cards = Array.from(document.querySelectorAll('[data-project-card]'));
        const resultCount = document.querySelector('[data-result-count]');
        const empty = document.querySelector('[data-empty]');
        const normalize = (value) => String(value || '').toLocaleLowerCase('pl-PL');
        const filter = () => {
            const data = new FormData(form);
            const query = normalize(data.get('query'));
            const area = String(data.get('area') || '');
            const category = String(data.get('category') || '');
            const status = String(data.get('status') || '');
            let visible = 0;
            cards.forEach((card) => {
                const matches = (!query || normalize(card.dataset.search).includes(query))
                    && (!area || card.dataset.area === area)
                    && (!category || card.dataset.category === category)
                    && (!status || card.dataset.status === status);
                card.classList.toggle('is-hidden', !matches);
                if (matches) visible += 1;
            });
            if (resultCount) resultCount.textContent = String(visible);
            if (empty) empty.hidden = visible !== 0;
        };
        const syncUrl = () => {
            const data = new FormData(form);
            const params = new URLSearchParams(window.location.search);
            const mapping = [
                ['query', 'q'],
                ['area', 'area_id'],
                ['category', 'category_id'],
            ];
            mapping.forEach(([field, param]) => {
                const value = String(data.get(field) || '').trim();
                if (value) {
                    params.set(param, value);
                } else {
                    params.delete(param);
                }
            });
            const queryString = params.toString();
            window.history.replaceState({}, '', `${window.location.pathname}${queryString ? `?${queryString}` : ''}`);
        };
        form.addEventListener('input', filter);
        form.addEventListener('input', syncUrl);
        form.addEventListener('reset', () => window.setTimeout(() => {
            filter();
            syncUrl();
        }, 0));
        filter();
    }
}

function mountAllInOneAccessibilityWidget() {
    const slot = document.querySelector('[data-aioa-slot]');
    const toggle = document.getElementById('accessibility_settings_toggle');

    if (!slot || !toggle || toggle.parentElement === slot) {
        return;
    }

    toggle.setAttribute('aria-label', toggle.getAttribute('aria-label') || 'Otwórz widget dostępności');
    toggle.setAttribute('title', toggle.getAttribute('title') || 'Ułatwienia dostępności');
    slot.appendChild(toggle);
}

function render() {
    root.innerHTML = layout(renderRoute());
    bindActions();
    mountAllInOneAccessibilityWidget();
}

function init() {
    const storedLanguage = window.localStorage.getItem('bo:lang');
    document.documentElement.lang = storedLanguage || 'pl';
    document.body.dataset.contrast = document.body.dataset.contrast || 'base';
    document.body.dataset.font = document.body.dataset.font || 'base';
    render();

    const observer = new MutationObserver(mountAllInOneAccessibilityWidget);
    observer.observe(document.body, { childList: true, subtree: true });
}

window.addEventListener('popstate', render);
init();

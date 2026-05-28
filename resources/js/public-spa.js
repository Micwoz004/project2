const state = window.BO_SPA || {};
const root = document.getElementById('bo-spa-root');
let cleanupNavDropdown = null;
let cleanupMobileNav = null;
let cleanupRevealMotion = null;

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

function oldValue(name, fallback = '') {
    const old = state.app?.old || {};
    return old[name] ?? fallback ?? '';
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
        slug: page.slug,
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

function renderInfoSubnav(activeSlug) {
    const items = infoMenuItems();
    if (!items.length) return '';

    return `
        <aside class="info-subnav" aria-labelledby="info-subnav-title">
            <p id="info-subnav-title" class="info-subnav-title">Informacje</p>
            <nav aria-label="Podstrony informacyjne">
                <ul>
                    ${items.map((item) => {
                        const itemPath = new URL(item.url, window.location.origin).pathname;
                        const isCurrent = item.slug === activeSlug || currentPath() === itemPath;
                        return `
                            <li>
                                <a href="${escapeHtml(item.url)}" data-spa-link ${isCurrent ? 'aria-current="page"' : ''}>
                                    ${escapeHtml(item.title)}
                                </a>
                            </li>
                        `;
                    }).join('')}
                </ul>
            </nav>
        </aside>
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
    const residentNav = state.app?.authenticated ? `
        <li><a class="nav-link" href="${href('residentDashboard')}" data-spa-link ${active('/panel') ? 'aria-current="page"' : ''}>Panel</a></li>
        <li><a class="nav-link" href="${href('residentProjects')}" data-spa-link ${active('/moje-projekty') && !active('/moje-projekty/zglos') ? 'aria-current="page"' : ''}>Moje projekty</a></li>
        <li><a class="nav-link" href="${href('residentAccount')}" data-spa-link ${active('/konto') ? 'aria-current="page"' : ''}>Konto</a></li>
        <li>
            <form class="nav-form" method="post" action="${href('logout')}">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <button class="nav-link nav-button" type="submit">Wyloguj</button>
            </form>
        </li>
    ` : `
        <li><a class="nav-link" href="${href('login')}" data-spa-link ${active('/login') ? 'aria-current="page"' : ''}>Zaloguj</a></li>
    `;
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
            <button class="nav-toggle" type="button" data-nav-toggle aria-controls="primary-navigation" aria-expanded="false">
                <span class="nav-toggle-icon" aria-hidden="true"></span>
                <span class="nav-toggle-label">Menu</span>
            </button>
            <nav id="primary-navigation" class="main-nav" data-nav-menu aria-label="Główna nawigacja">
                <ul class="nav-list">
                    <li><a class="nav-link" href="${href('projects')}" data-spa-link ${active('/projekty') || active('/projekt') ? 'aria-current="page"' : ''} data-i18n="navProjects">${c.navProjects}</a></li>
                    ${renderInfoDropdown(c.navInfo)}
                    ${residentNav}
                    <li><a class="btn btn-primary" href="${state.app?.authenticated ? href('residentSubmit') : href('submit')}" data-spa-link data-i18n="navSubmit">${c.navSubmit}</a></li>
                </ul>
            </nav>
        </header>
        <main id="main" class="page-shell">
            ${state.app?.flash ? `<p class="spa-flash" role="status">${escapeHtml(state.app.flash)}</p>` : ''}
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

function scheduleBand(modifier = '') {
    return `
        <section class="schedule-band${modifier ? ` ${modifier}` : ''}" aria-labelledby="process-title">
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
            <div class="section-head projects-section-head">
                <div>
                    <p class="eyebrow">Katalog projektów</p>
                    <h1 id="projects-title">Wybierz projekt do sprawdzenia</h1>
                </div>
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

function residentProfile() {
    return state.resident?.profile || {};
}

function loginView() {
    const errors = Object.values(state.app?.errors || {}).flat();
    return `
        <section class="resident-page-head login-page-head" aria-labelledby="login-title">
            <p class="eyebrow">Konto mieszkańca</p>
            <h1 id="login-title">Zaloguj się do panelu mieszkańca.</h1>
            <p class="lead">Po zalogowaniu możesz zgłosić projekt, śledzić status swoich spraw i zaktualizować dane kontaktowe.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="account-grid login-grid" aria-label="Logowanie mieszkańca">
            <form class="resident-form account-form login-form" method="post" action="${href('loginPost')}">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <fieldset>
                    <legend>Logowanie</legend>
                    <div class="field">
                        <label for="email">Adres e-mail</label>
                        <input id="email" name="email" type="email" required autocomplete="email" value="${escapeHtml(oldValue('email'))}">
                    </div>
                    <div class="field">
                        <label for="password">Hasło</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                    </div>
                    <label class="check-row"><input name="remember" type="checkbox" value="1"><span>Zapamiętaj mnie na tym urządzeniu.</span></label>
                </fieldset>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Zaloguj</button>
                </div>
            </form>

            <aside class="account-side login-side">
                <article class="identity-card">
                    <span class="status status-live">Konto mieszkańca</span>
                    <h2>Zaloguj się</h2>
                    <div class="login-help-actions" aria-label="Pomoc przy logowaniu">
                        <a href="${href('passwordRequest')}" data-spa-link>Nie pamiętasz hasła?</a>
                        <a href="${href('register')}" data-spa-link>Załóż konto mieszkańca</a>
                    </div>
                </article>
            </aside>
        </section>
    `;
}

function registerView() {
    const errors = Object.values(state.app?.errors || {}).flat();
    return `
        <section class="resident-page-head login-page-head" aria-labelledby="register-title">
            <p class="eyebrow">Rejestracja mieszkańca</p>
            <h1 id="register-title">Załóż konto mieszkańca.</h1>
            <p class="lead">Konto pozwala zgłaszać projekty, śledzić ich status i obsługiwać korekty w jednym panelu.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="account-grid login-grid" aria-label="Rejestracja mieszkańca">
            <form class="resident-form account-form login-form" method="post" action="${href('registerPost')}">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <fieldset>
                    <legend>Rejestracja</legend>
                    <div class="two-col">
                        <div class="field">
                            <label for="first_name">Imię</label>
                            <input id="first_name" name="first_name" required maxlength="127" autocomplete="given-name" value="${escapeHtml(oldValue('first_name'))}">
                        </div>
                        <div class="field">
                            <label for="last_name">Nazwisko</label>
                            <input id="last_name" name="last_name" required maxlength="127" autocomplete="family-name" value="${escapeHtml(oldValue('last_name'))}">
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Adres e-mail</label>
                        <input id="email" name="email" type="email" required autocomplete="email" value="${escapeHtml(oldValue('email'))}">
                    </div>
                    <div class="two-col">
                        <div class="field">
                            <label for="password">Hasło</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Powtórz hasło</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        </div>
                    </div>
                </fieldset>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Załóż konto</button>
                    <a class="btn btn-secondary" href="${href('login')}" data-spa-link>Wróć do logowania</a>
                </div>
            </form>

            <aside class="account-side login-side">
                <article class="identity-card">
                    <span class="status status-live">Nowe konto</span>
                    <h2>Panel mieszkańca</h2>
                    <p>Po rejestracji od razu przejdziesz do swojego panelu i formularza zgłoszenia projektu.</p>
                </article>
            </aside>
        </section>
    `;
}

function passwordRequestView() {
    const errors = Object.values(state.app?.errors || {}).flat();
    return `
        <section class="resident-page-head login-page-head" aria-labelledby="password-request-title">
            <p class="eyebrow">Reset hasła</p>
            <h1 id="password-request-title">Ustaw nowe hasło do konta.</h1>
            <p class="lead">Podaj adres e-mail konta mieszkańca. Jeśli konto istnieje, wyślemy link do ustawienia nowego hasła.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="account-grid login-grid" aria-label="Reset hasła">
            <form class="resident-form account-form login-form" method="post" action="${href('passwordEmail')}">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <fieldset>
                    <legend>Reset hasła</legend>
                    <div class="field">
                        <label for="email">Adres e-mail</label>
                        <input id="email" name="email" type="email" required autocomplete="email" value="${escapeHtml(oldValue('email'))}">
                    </div>
                </fieldset>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Wyślij link</button>
                    <a class="btn btn-secondary" href="${href('login')}" data-spa-link>Wróć do logowania</a>
                </div>
            </form>

            <aside class="account-side login-side">
                <article class="identity-card">
                    <span class="status status-live">Pomoc</span>
                    <h2>Link e-mail</h2>
                    <p>Link resetujący hasło jest czasowy. Po zmianie hasła zalogujesz się już nowymi danymi.</p>
                </article>
            </aside>
        </section>
    `;
}

function passwordResetView() {
    const errors = Object.values(state.app?.errors || {}).flat();
    const token = currentPath().split('/').pop();
    return `
        <section class="resident-page-head login-page-head" aria-labelledby="password-reset-title">
            <p class="eyebrow">Nowe hasło</p>
            <h1 id="password-reset-title">Wpisz nowe hasło.</h1>
            <p class="lead">Po zapisaniu wrócisz do ekranu logowania mieszkańca.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="account-grid login-grid" aria-label="Ustawienie nowego hasła">
            <form class="resident-form account-form login-form" method="post" action="${href('passwordUpdate')}">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <input type="hidden" name="token" value="${escapeHtml(token)}">
                <fieldset>
                    <legend>Nowe hasło</legend>
                    <div class="field">
                        <label for="email">Adres e-mail</label>
                        <input id="email" name="email" type="email" required autocomplete="email" value="${escapeHtml(oldValue('email', queryValue('email')))}">
                    </div>
                    <div class="two-col">
                        <div class="field">
                            <label for="password">Nowe hasło</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Powtórz nowe hasło</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        </div>
                    </div>
                </fieldset>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Zmień hasło</button>
                </div>
            </form>

            <aside class="account-side login-side">
                <article class="identity-card">
                    <span class="status status-live">Bezpieczeństwo</span>
                    <h2>Nowe dane</h2>
                    <p>Użyj hasła innego niż w pozostałych serwisach i zachowaj je tylko dla siebie.</p>
                </article>
            </aside>
        </section>
    `;
}

function residentName() {
    const profile = residentProfile();
    return [profile.firstName, profile.lastName].filter(Boolean).join(' ') || profile.name || 'Mieszkaniec';
}

function residentDashboardView() {
    const projects = state.resident?.projects || [];
    const priority = projects.find((project) => project.status === 'returned') || projects.find((project) => project.status === 'draft') || projects[0];
    const nextDeadline = state.edition?.stateDate || 'Wkrótce';
    return `
        <section class="resident-hero" aria-labelledby="resident-title">
            <div>
                <p class="eyebrow">Jesteś zalogowany jako mieszkaniec</p>
                <h1 id="resident-title">Dokończ zgłoszenie albo sprawdź swoje projekty.</h1>
                <p class="lead">Panel prowadzi przez zgłoszenie projektu, korektę, status weryfikacji i dane konta.</p>
                <div class="resident-actions">
                    <a class="btn btn-primary" href="${href('residentSubmit')}" data-spa-link>Nowy projekt</a>
                    <a class="btn btn-secondary" href="${href('residentProjects')}" data-spa-link>Moje projekty</a>
                </div>
            </div>
        </section>

        <section class="resident-grid" aria-label="Podsumowanie konta">
            <article class="resident-stat">
                <span>Wersje robocze</span>
                <strong>${escapeHtml(state.resident?.stats?.drafts ?? 0)}</strong>
                <p>Projekty zapisane jako kopia robocza mieszkańca.</p>
            </article>
            <article class="resident-stat">
                <span>W weryfikacji</span>
                <strong>${escapeHtml(state.resident?.stats?.verification ?? 0)}</strong>
                <p>Sprawy, które urząd analizuje formalnie lub merytorycznie.</p>
            </article>
            <article class="resident-stat">
                <span>Do poprawy</span>
                <strong>${escapeHtml(state.resident?.stats?.corrections ?? 0)}</strong>
                <p>Projekty z aktywnym terminem korekty.</p>
            </article>
        </section>

        <section class="resident-layout" aria-labelledby="next-title">
            <div class="resident-main">
                <div class="section-head compact">
                    <div>
                        <p class="eyebrow">Co dalej?</p>
                        <h2 id="next-title">Twoje najważniejsze sprawy</h2>
                    </div>
                    <p class="lead">Najbliższa akcja wynika z realnego statusu Twoich zgłoszeń.</p>
                </div>
                ${priority ? residentTaskCard(priority) : `
                    <article class="task-card is-priority">
                        <div>
                            <span class="status status-live">Start</span>
                            <h3>Nie masz jeszcze projektu w tej edycji</h3>
                            <p>Utwórz zgłoszenie, dodaj kosztorys i załączniki, a system przekaże je do weryfikacji.</p>
                        </div>
                        <a class="btn btn-primary" href="${href('residentSubmit')}" data-spa-link>Zgłoś projekt</a>
                    </article>
                `}
                <article class="task-card">
                    <div>
                        <span class="status status-live">Etap</span>
                        <h3>${escapeHtml(state.edition?.stateLabel || 'Budżet obywatelski')}</h3>
                        <p>Najbliższy termin: ${escapeHtml(nextDeadline)}.</p>
                    </div>
                    <a class="btn btn-secondary" href="/informacje/harmonogram" data-spa-link>Harmonogram</a>
                </article>
            </div>
        </section>

        <section class="resident-support-grid" aria-label="Terminy i stan konta">
            <aside class="deadline-card deadline-card-inline" aria-label="Najbliższy termin">
                <span class="status status-live">${escapeHtml(state.edition?.stateLabel || 'Aktywny etap')}</span>
                <strong>${escapeHtml(nextDeadline)}</strong>
                <p>najbliższa data w aktualnej edycji</p>
            </aside>

            <aside class="resident-aside resident-aside-static" aria-labelledby="account-state-title">
                <h2 id="account-state-title">Stan konta</h2>
                <ul class="check-list">
                    <li class="${residentProfile().firstName && residentProfile().lastName ? 'is-done' : ''}">Profil podstawowy uzupełniony</li>
                    <li class="${residentProfile().emailVerified ? 'is-done' : ''}">Adres e-mail potwierdzony</li>
                    <li class="${residentProfile().hasAddress ? 'is-done' : ''}">Adres zamieszkania uzupełniony</li>
                    <li class="${residentProfile().phone ? 'is-done' : ''}">Telefon kontaktowy uzupełniony</li>
                </ul>
                <a class="btn btn-secondary" href="${href('residentAccount')}" data-spa-link>Zarządzaj kontem</a>
            </aside>
        </section>
    `;
}

function residentTaskCard(project) {
    const isPriority = project.status === 'returned' || project.status === 'draft';
    const actionUrl = project.status === 'returned' && project.correctionUrl ? project.correctionUrl : href('residentProjects');
    const actionLabel = project.status === 'returned' ? 'Uzupełnij' : project.status === 'draft' ? 'Sprawdź' : 'Zobacz';
    return `
        <article class="task-card ${isPriority ? 'is-priority' : ''}">
            <div>
                <span class="status ${residentStatusClass(project.status)}">${escapeHtml(project.statusLabel)}</span>
                <h3>${escapeHtml(project.title)}</h3>
                <p>${escapeHtml(project.correction?.notes || project.description || 'Sprawdź aktualny status zgłoszenia.')}</p>
            </div>
            <a class="btn ${isPriority ? 'btn-primary' : 'btn-secondary'}" href="${escapeHtml(actionUrl)}" data-spa-link>${actionLabel}</a>
        </article>
    `;
}

function residentStatusClass(status) {
    return {
        draft: 'status-draft',
        returned: 'status-returned',
        waiting: 'status-waiting',
        live: 'status-live',
        ended: 'status-ended',
    }[status] || 'status-waiting';
}

function residentProjectsView() {
    const projects = state.resident?.projects || [];
    const categories = [...new Set(projects.map((project) => project.category).filter(Boolean))];
    return `
        <section class="resident-page-head" aria-labelledby="my-projects-title">
            <p class="eyebrow">Moje projekty</p>
            <h1 id="my-projects-title">Moje projekty</h1>
            <p class="lead">Statusy zgłoszeń, terminy odpowiedzi i szybkie akcje w jednym widoku.</p>
        </section>

        <section class="resident-project-layout" aria-label="Lista moich projektów">
            <aside class="filter-panel resident-filter" aria-labelledby="my-filter-title">
                <h2 id="my-filter-title">Filtry</h2>
                <form data-project-filters>
                    <div class="field">
                        <label for="my-query">Szukaj</label>
                        <input id="my-query" name="query" type="search" placeholder="Nazwa, kategoria, obszar">
                    </div>
                    <div class="field">
                        <label for="my-category">Kategoria</label>
                        <select id="my-category" name="category">
                            <option value="">Wszystkie</option>
                            ${categories.map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="field">
                        <label for="my-status">Status</label>
                        <select id="my-status" name="status">
                            <option value="">Wszystkie</option>
                            <option value="draft">Roboczy</option>
                            <option value="waiting">W weryfikacji</option>
                            <option value="returned">Do poprawy</option>
                            <option value="live">Opublikowany</option>
                            <option value="ended">Zakończony</option>
                        </select>
                    </div>
                    <button class="btn btn-secondary" type="reset">Wyczyść</button>
                </form>
                <p class="form-hint"><span data-result-count>0</span> projektów</p>
            </aside>

            <div class="resident-project-results">
                <p class="panel" data-empty hidden>Nie znaleziono projektów dla tych filtrów.</p>
                <div class="my-project-list">
                    ${projects.map(residentProjectCard).join('') || `
                        <article class="my-project-card">
                            <span class="status status-live">Start</span>
                            <h2>Brak zgłoszonych projektów</h2>
                            <p>Po wysłaniu formularza projekt pojawi się na tej liście razem ze statusem weryfikacji.</p>
                            <div class="project-actions">
                                <a class="btn btn-primary" href="${href('residentSubmit')}" data-spa-link>Zgłoś projekt</a>
                            </div>
                        </article>
                    `}
                </div>
            </div>
        </section>
    `;
}

function residentProjectCard(project) {
    const search = [project.title, project.area, project.category, project.description, project.publicStatusLabel].join(' ');
    const actionUrl = project.status === 'returned' && project.correctionUrl
        ? project.correctionUrl
        : (project.publicUrl || href('residentProjects'));
    const actionLabel = project.status === 'returned' ? 'Uzupełnij' : (project.publicUrl ? 'Podgląd' : 'Na liście');
    return `
        <article class="my-project-card" data-project-card data-category="${escapeHtml(project.category)}" data-status="${escapeHtml(project.status)}" data-search="${escapeHtml(search)}">
            <div class="my-project-top">
                <span class="status ${residentStatusClass(project.status)}">${escapeHtml(project.statusLabel)}</span>
                <span class="notice-date">${project.correction?.deadline ? `Odpowiedz do ${escapeHtml(project.correction.deadline)}` : escapeHtml(project.submittedAt || 'Zgłoszenie')}</span>
            </div>
            <h2>${escapeHtml(project.title)}</h2>
            <p>${escapeHtml(project.correction?.notes || project.description || 'Projekt jest zapisany w systemie.')}</p>
            <p class="project-meta">${escapeHtml(project.category)} · ${escapeHtml(project.area)} · ${escapeHtml(project.costLabel)}</p>
            <div class="progress-line" aria-label="Kompletność projektu">
                <span style="width: ${escapeHtml(project.progress)}%"></span>
            </div>
            <div class="project-actions">
                <a class="btn ${project.status === 'returned' ? 'btn-primary' : 'btn-secondary'}" href="${escapeHtml(actionUrl)}" ${project.publicUrl ? '' : 'data-spa-link'}>${actionLabel}</a>
                <button class="btn btn-secondary" type="button" data-save-action>Obserwuj</button>
            </div>
        </article>
    `;
}

function residentCorrectionView() {
    const projectId = currentPath().match(/^\/moje-projekty\/(\d+)\/korekta$/)?.[1];
    const project = (state.resident?.projects || []).find((item) => String(item.id) === String(projectId));
    const errors = Object.values(state.app?.errors || {}).flat();

    if (!project || !project.correction) {
        return `
            <section class="resident-page-head" aria-labelledby="correction-missing-title">
                <p class="eyebrow">Korekta projektu</p>
                <h1 id="correction-missing-title">Brak aktywnej korekty.</h1>
                <p class="lead">Projekt nie ma obecnie otwartego wezwania do poprawy albo nie należy do Twojego konta.</p>
                <a class="btn btn-secondary" href="${href('residentProjects')}" data-spa-link>Wróć do moich projektów</a>
            </section>
        `;
    }

    const allowed = project.correction.allowedFields || [];
    const costItems = project.costItems?.length ? project.costItems : [{ description: '', amount: '' }];

    return `
        <section class="resident-page-head" aria-labelledby="correction-title">
            <p class="eyebrow">Korekta projektu</p>
            <h1 id="correction-title">Uzupełnij projekt: ${escapeHtml(project.title)}</h1>
            <p class="lead">Możesz poprawić tylko pola odblokowane w wezwaniu do korekty. Termin: ${escapeHtml(project.correction.deadline || 'do ustalenia')}.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="form-workspace" aria-label="Formularz korekty projektu">
            <aside class="step-sidebar">
                <span class="status status-returned">Do poprawy</span>
                <h2>Zakres korekty</h2>
                <p>${escapeHtml(project.correction.notes || 'Popraw pola wskazane przez urząd i zapisz korektę.')}</p>
                <a class="btn btn-secondary" href="${href('residentProjects')}" data-spa-link>Moje projekty</a>
            </aside>

            <form class="resident-form" method="post" action="${escapeHtml(project.correctionUpdateUrl)}" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <input type="hidden" name="_method" value="PUT">

                <fieldset>
                    <legend>Dane projektu</legend>
                    ${correctionSelectField(allowed, 'project_area_id', 'Obszar', state.areas || [], project.projectAreaId)}
                    ${correctionSelectField(allowed, 'category_id', 'Kategoria', state.categories || [], project.categoryId)}
                    ${correctionInputField(allowed, 'title', 'Tytuł', project.title, 600)}
                    ${correctionTextareaField(allowed, 'localization', 'Lokalizacja', project.localization)}
                    ${correctionMapField(allowed, project.mapData)}
                    ${correctionTextareaField(allowed, 'description', 'Opis', project.description)}
                    ${correctionTextareaField(allowed, 'goal', 'Cel', project.goal)}
                    ${correctionTextareaField(allowed, 'argumentation', 'Uzasadnienie', project.argumentation)}
                    ${correctionTextareaField(allowed, 'availability', 'Dostępność', project.availability)}
                    ${correctionTextareaField(allowed, 'recipients', 'Odbiorcy', project.recipients)}
                    ${correctionTextareaField(allowed, 'free_of_charge', 'Bezpłatność', project.freeOfCharge)}
                    ${allowed.length ? '' : '<p class="form-hint">Urząd nie odblokował pól tekstowych do poprawy.</p>'}
                </fieldset>

                ${allowed.includes('cost') ? `
                    <fieldset>
                        <legend>Kosztorys</legend>
                        ${costItems.map((item, index) => `
                            <div class="two-col">
                                <div class="field">
                                    <label for="cost_items_${index}_description">Opis pozycji</label>
                                    <input id="cost_items_${index}_description" name="cost_items[${index}][description]" required maxlength="1000" value="${escapeHtml(item.description)}">
                                </div>
                                <div class="field">
                                    <label for="cost_items_${index}_amount">Kwota</label>
                                    <input id="cost_items_${index}_amount" name="cost_items[${index}][amount]" required type="number" step="0.01" min="0" data-budget-input value="${escapeHtml(item.amount)}">
                                </div>
                            </div>
                        `).join('')}
                        <div class="budget-summary">
                            <span>Szacowany koszt łącznie</span>
                            <strong data-budget-total>0 zł</strong>
                        </div>
                    </fieldset>
                ` : ''}

                ${correctionAttachmentsFieldset(allowed)}

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Zapisz korektę</button>
                </div>
            </form>
        </section>
    `;
}

function correctionSelectField(allowed, name, label, options, value) {
    if (!allowed.includes(name)) return '';
    return `
        <div class="field">
            <label for="${name}">${label}</label>
            <select id="${name}" name="${name}" required>
                ${options.map((option) => `<option value="${option.id}" ${String(value || '') === String(option.id) ? 'selected' : ''}>${escapeHtml(option.name)}</option>`).join('')}
            </select>
        </div>
    `;
}

function correctionInputField(allowed, name, label, value, maxLength) {
    if (!allowed.includes(name)) return '';
    return `
        <div class="field">
            <label for="${name}">${label}</label>
            <input id="${name}" name="${name}" required maxlength="${maxLength}" value="${escapeHtml(oldValue(name, value))}">
        </div>
    `;
}

function correctionTextareaField(allowed, name, label, value) {
    if (!allowed.includes(name)) return '';
    return `
        <div class="field">
            <label for="${name}">${label}</label>
            <textarea id="${name}" name="${name}" required>${escapeHtml(oldValue(name, value))}</textarea>
        </div>
    `;
}

function correctionMapField(allowed, value) {
    if (!allowed.includes('map_data')) return '';
    const mapData = oldValue('map_data', JSON.stringify(value || { type: 'FeatureCollection', features: [] }, null, 2));
    return `
        <div class="field">
            <label for="map_data">Dane mapy JSON</label>
            <textarea id="map_data" name="map_data">${escapeHtml(mapData)}</textarea>
        </div>
    `;
}

function correctionAttachmentsFieldset(allowed) {
    const fields = [
        ['support_attachment', 'support_list_files', 'Listy poparcia'],
        ['agreement_attachment', 'owner_agreement_files', 'Zgody właściciela'],
        ['map_attachment', 'map_files', 'Załączniki mapy'],
        ['parent_agreement_attachment', 'parent_agreement_files', 'Zgody rodzica lub opiekuna'],
        ['attachments', 'attachment_files', 'Pozostałe załączniki'],
    ].filter(([field]) => allowed.includes(field));

    if (!fields.length) return '';

    return `
        <fieldset>
            <legend>Załączniki</legend>
            ${fields.map(([, name, label]) => `
                <div class="field">
                    <label for="${name}">${label}</label>
                    <input id="${name}" name="${name}[]" type="file" multiple>
                </div>
            `).join('')}
        </fieldset>
    `;
}

function residentSubmitView() {
    const errors = Object.values(state.app?.errors || {}).flat();
    const profile = residentProfile();
    const editionId = oldValue('budget_edition_id', state.edition?.id || '');
    return `
        <section class="resident-page-head" aria-labelledby="submit-resident-title">
            <p class="eyebrow">Formularz mieszkańca</p>
            <h1 id="submit-resident-title">Zgłoś projekt do budżetu obywatelskiego.</h1>
            <p class="lead">Formularz zapisuje zgłoszenie przez realny endpoint systemu i uruchamia istniejącą walidację projektu.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="form-workspace" aria-label="Formularz zgłaszania projektu">
            <aside class="step-sidebar">
                <span class="status status-live">Nowy projekt</span>
                <h2>Postęp zgłoszenia</h2>
                <ol class="step-list">
                    <li class="is-active">Dane autora</li>
                    <li>Opis projektu</li>
                    <li>Koszt i załączniki</li>
                    <li>Oświadczenia</li>
                </ol>
                <p>Po wysłaniu projekt trafi do Twojej listy projektów i do weryfikacji formalnej.</p>
            </aside>

            <form class="resident-form" method="post" action="${href('projectStore')}" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <input type="hidden" name="budget_edition_id" value="${escapeHtml(editionId)}">
                <input type="hidden" name="map_data" value='{"type":"FeatureCollection","features":[]}'>
                <input type="hidden" name="contact_with" value="1">

                <fieldset>
                    <legend>Dane autora</legend>
                    <div class="two-col">
                        <div class="field"><label for="author_first_name">Imię</label><input id="author_first_name" name="author_first_name" required maxlength="127" value="${escapeHtml(oldValue('author_first_name', profile.firstName))}"></div>
                        <div class="field"><label for="author_last_name">Nazwisko</label><input id="author_last_name" name="author_last_name" required maxlength="127" value="${escapeHtml(oldValue('author_last_name', profile.lastName))}"></div>
                    </div>
                    <div class="two-col">
                        <div class="field"><label for="author_email">E-mail</label><input id="author_email" name="author_email" type="email" required maxlength="255" value="${escapeHtml(oldValue('author_email', profile.email))}"></div>
                        <div class="field"><label for="author_phone">Telefon</label><input id="author_phone" name="author_phone" maxlength="30" value="${escapeHtml(oldValue('author_phone', profile.phone))}"></div>
                    </div>
                    <div class="two-col">
                        <div class="field"><label for="author_street">Ulica</label><input id="author_street" name="author_street" maxlength="127" value="${escapeHtml(oldValue('author_street', profile.street))}"></div>
                        <div class="field"><label for="author_house_no">Nr domu</label><input id="author_house_no" name="author_house_no" maxlength="20" value="${escapeHtml(oldValue('author_house_no', profile.houseNo))}"></div>
                    </div>
                    <div class="two-col">
                        <div class="field"><label for="author_flat_no">Nr lokalu</label><input id="author_flat_no" name="author_flat_no" maxlength="20" value="${escapeHtml(oldValue('author_flat_no', profile.flatNo))}"></div>
                        <div class="field"><label for="author_post_code">Kod pocztowy</label><input id="author_post_code" name="author_post_code" maxlength="6" value="${escapeHtml(oldValue('author_post_code', profile.postCode))}"></div>
                    </div>
                    <div class="field"><label for="author_city">Miejscowość</label><input id="author_city" name="author_city" maxlength="127" value="${escapeHtml(oldValue('author_city', profile.city))}"></div>
                    <label class="check-row"><input name="author_email_agree" type="checkbox" value="1" checked><span>Publikowana forma kontaktu: e-mail.</span></label>
                    <label class="check-row"><input name="author_phone_agree" type="checkbox" value="1"><span>Publikowana forma kontaktu: telefon.</span></label>
                </fieldset>

                <fieldset>
                    <legend>Opis projektu</legend>
                    <div class="field"><label for="title">Tytuł projektu</label><input id="title" name="title" required maxlength="600" value="${escapeHtml(oldValue('title'))}" placeholder="np. Zielony skwer przy bibliotece"></div>
                    <div class="two-col">
                        <div class="field">
                            <label for="project_area_id">Obszar</label>
                            <select id="project_area_id" name="project_area_id" required>
                                ${(state.areas || []).map((area) => `<option value="${area.id}" ${String(oldValue('project_area_id')) === String(area.id) ? 'selected' : ''}>${escapeHtml(area.name)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="field">
                            <label for="category_id">Kategoria</label>
                            <select id="category_id" name="category_id" required>
                                ${(state.categories || []).map((category) => `<option value="${category.id}" ${String(oldValue('category_id')) === String(category.id) ? 'selected' : ''}>${escapeHtml(category.name)}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="local">Typ projektu</label>
                        <select id="local" name="local" required>
                            <option value="1" ${String(oldValue('local', '1')) === '1' ? 'selected' : ''}>Projekt lokalny</option>
                            <option value="2" ${String(oldValue('local')) === '2' ? 'selected' : ''}>Projekt Zielonego BO</option>
                        </select>
                    </div>
                    <div class="field"><label for="short_description">Krótki opis</label><textarea id="short_description" name="short_description" maxlength="700">${escapeHtml(oldValue('short_description'))}</textarea></div>
                    ${textareaField('localization', 'Lokalizacja', true, oldValue('localization'))}
                    <div class="field"><label for="address">Adres</label><input id="address" name="address" maxlength="300" value="${escapeHtml(oldValue('address'))}"></div>
                    ${textareaField('plot', 'Działka', false, oldValue('plot'))}
                    <div class="map-mini" role="img" aria-label="Miejsce na mapę lokalizacji projektu"><span class="pin"></span></div>
                    ${textareaField('description', 'Opis', true, oldValue('description'))}
                    ${textareaField('goal', 'Cel', true, oldValue('goal'))}
                    ${textareaField('argumentation', 'Uzasadnienie', true, oldValue('argumentation'))}
                    ${textareaField('availability', 'Dostępność', true, oldValue('availability'))}
                    ${textareaField('recipients', 'Odbiorcy', true, oldValue('recipients'))}
                    ${textareaField('free_of_charge', 'Bezpłatność', true, oldValue('free_of_charge'))}
                    ${textareaField('additional_cost', 'Koszty utrzymania w kolejnych latach', false, oldValue('additional_cost'))}
                </fieldset>

                <fieldset>
                    <legend>Koszt i załączniki</legend>
                    <div class="two-col">
                        <div class="field"><label for="cost_description">Składowa kosztów</label><input id="cost_description" name="cost_items[0][description]" required maxlength="1000" value="${escapeHtml(oldValue('cost_description'))}"></div>
                        <div class="field"><label for="cost_amount">Koszt brutto</label><input id="cost_amount" name="cost_items[0][amount]" required type="number" min="0" step="0.01" data-budget-input value="${escapeHtml(oldValue('cost_amount'))}"></div>
                    </div>
                    <div class="budget-summary">
                        <span>Szacowany koszt łącznie</span>
                        <strong data-budget-total>${escapeHtml(oldValue('cost_amount', '0'))} zł</strong>
                    </div>
                    <div class="field"><label for="support_list_file">Plik listy poparcia</label><input id="support_list_file" name="support_list_file" type="file" required></div>
                    <div class="two-col">
                        <div class="field"><label for="owner_agreement_files">Zgody właściciela</label><input id="owner_agreement_files" name="owner_agreement_files[]" type="file" multiple></div>
                        <div class="field"><label for="map_files">Załączniki mapy</label><input id="map_files" name="map_files[]" type="file" multiple></div>
                        <div class="field"><label for="parent_agreement_files">Zgody rodzica lub opiekuna</label><input id="parent_agreement_files" name="parent_agreement_files[]" type="file" multiple></div>
                        <div class="field"><label for="attachment_files">Pozostałe załączniki</label><input id="attachment_files" name="attachment_files[]" type="file" multiple></div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Oświadczenia</legend>
                    <label class="check-row"><input name="author_read_confirm" type="checkbox" value="1" required><span>${escapeHtml(state.legacyText?.regulation_confirmation || 'Potwierdzam zapoznanie się z regulaminem.')}</span></label>
                    <label class="check-row"><input name="author_personal_data_agree" type="checkbox" value="1"><span>${escapeHtml(state.legacyText?.evaluation_consent_checkbox || 'Wyrażam zgodę na przetwarzanie danych do weryfikacji projektu.')}</span></label>
                    <label class="check-row"><input name="show_task_coauthors" type="checkbox" value="1" checked><span>Informacje o współautorze mają być wyświetlane.</span></label>
                    <label class="check-row"><input name="consent_to_change" type="checkbox" value="1"><span>${escapeHtml(state.legacyText?.consent_to_change || 'Wyrażam zgodę na ewentualne zmiany projektu po konsultacji.')}</span></label>
                    <label class="check-row"><input name="attachments_anonymized" type="checkbox" value="1" required><span>${escapeHtml(state.legacyText?.attachments_anonymized || 'Załączniki nie zawierają danych wymagających ukrycia.')}</span></label>
                    <label class="check-row"><input name="support_list" type="checkbox" value="1" required><span>${escapeHtml(state.legacyText?.support_list || 'Dołączam listę poparcia.')}</span></label>
                </fieldset>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Wyślij do weryfikacji</button>
                </div>
            </form>
        </section>
    `;
}

function residentAccountView() {
    const profile = residentProfile();
    const errors = Object.values(state.app?.errors || {}).flat();
    return `
        <section class="resident-page-head" aria-labelledby="account-title">
            <p class="eyebrow">Konto mieszkańca</p>
            <h1 id="account-title">Konto mieszkańca</h1>
            <p class="lead">Dane kontaktowe, adresowe i podstawowe bezpieczeństwo.</p>
        </section>

        ${errors.length ? `<div class="panel form-errors">${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>` : ''}

        <section class="account-grid" aria-label="Zarządzanie kontem">
            <form class="resident-form account-form" method="post" action="${href('residentAccountUpdate')}">
                <input type="hidden" name="_token" value="${escapeHtml(state.app?.csrfToken)}">
                <input type="hidden" name="_method" value="PATCH">
                <fieldset>
                    <legend>Dane podstawowe</legend>
                    <div class="two-col">
                        <div class="field"><label for="first_name">Imię</label><input id="first_name" name="first_name" required maxlength="127" value="${escapeHtml(oldValue('first_name', profile.firstName))}"></div>
                        <div class="field"><label for="last_name">Nazwisko</label><input id="last_name" name="last_name" required maxlength="127" value="${escapeHtml(oldValue('last_name', profile.lastName))}"></div>
                    </div>
                    <div class="two-col">
                        <div class="field"><label for="email">Adres e-mail</label><input id="email" name="email" type="email" required maxlength="255" value="${escapeHtml(oldValue('email', profile.email))}"></div>
                        <div class="field"><label for="phone">Telefon</label><input id="phone" name="phone" maxlength="30" value="${escapeHtml(oldValue('phone', profile.phone))}"></div>
                    </div>
                    <div class="two-col">
                        <div class="field"><label for="street">Ulica</label><input id="street" name="street" maxlength="127" value="${escapeHtml(oldValue('street', profile.street))}"></div>
                        <div class="field"><label for="house_no">Nr domu</label><input id="house_no" name="house_no" maxlength="20" value="${escapeHtml(oldValue('house_no', profile.houseNo))}"></div>
                    </div>
                    <div class="two-col">
                        <div class="field"><label for="flat_no">Nr lokalu</label><input id="flat_no" name="flat_no" maxlength="20" value="${escapeHtml(oldValue('flat_no', profile.flatNo))}"></div>
                        <div class="field"><label for="post_code">Kod pocztowy</label><input id="post_code" name="post_code" maxlength="6" value="${escapeHtml(oldValue('post_code', profile.postCode))}"></div>
                    </div>
                    <div class="field"><label for="city">Miejscowość</label><input id="city" name="city" maxlength="127" value="${escapeHtml(oldValue('city', profile.city))}"></div>
                </fieldset>

                <fieldset>
                    <legend>Bezpieczeństwo</legend>
                    <div class="field"><label for="current_password">Obecne hasło</label><input id="current_password" name="current_password" type="password" autocomplete="current-password"></div>
                    <div class="two-col">
                        <div class="field"><label for="password">Nowe hasło</label><input id="password" name="password" type="password" autocomplete="new-password"></div>
                        <div class="field"><label for="password_confirmation">Powtórz nowe hasło</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Zapisz ustawienia</button>
                </div>
            </form>

            <aside class="account-side">
                <article class="identity-card">
                    <span class="status status-live">Konto aktywne</span>
                    <h2>${escapeHtml(residentName())}</h2>
                    <p>${profile.emailVerified ? 'Adres e-mail jest potwierdzony.' : 'Adres e-mail oczekuje na potwierdzenie.'} Profil służy do obsługi zgłoszeń mieszkańca.</p>
                </article>
                <article class="security-card">
                    <h2>Stan konta</h2>
                    <ul class="check-list">
                        <li class="${profile.hasPassword ? 'is-done' : ''}">Hasło ustawione</li>
                        <li class="${profile.emailVerified ? 'is-done' : ''}">E-mail potwierdzony</li>
                        <li class="${profile.hasAddress ? 'is-done' : ''}">Adres do kontaktu uzupełniony</li>
                    </ul>
                    <a class="btn btn-secondary" href="${href('residentProjects')}" data-spa-link>Moje projekty</a>
                </article>
            </aside>
        </section>
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

function fallbackFaqItems() {
    return [
        {
            question: 'Kto może zgłosić projekt?',
            answer: '<p>Projekt może zgłosić mieszkaniec miasta. Szczegółowe warunki, w tym wiek lub wymagane zgody, należy opisać w lokalnym regulaminie.</p>',
        },
        {
            question: 'Czy projekt musi być inwestycją?',
            answer: '<p>Nie zawsze. W budżecie obywatelskim można prezentować zarówno zadania inwestycyjne, jak i działania społeczne, edukacyjne, sportowe lub kulturalne.</p>',
        },
        {
            question: 'Co oznacza ogólnodostępność?',
            answer: '<p>Efekt projektu powinien być dostępny dla szerokiej grupy mieszkańców, bez opłat i bez zamkniętego, prywatnego charakteru.</p>',
        },
        {
            question: 'Czy można poprawić projekt po wysłaniu?',
            answer: '<p>W typowym procesie urząd może poprosić autora o uzupełnienie braków lub doprecyzowanie zakresu w czasie weryfikacji.</p>',
        },
        {
            question: 'Jak wybierane są projekty do realizacji?',
            answer: '<p>Po głosowaniu projekty są układane według poparcia mieszkańców i finansowane do wyczerpania puli środków przewidzianej dla danej kategorii.</p>',
        },
    ];
}

function parseFaqItems(body) {
    const template = document.createElement('template');
    template.innerHTML = String(body || '').trim();

    const intro = [];
    const items = [];
    let current = null;

    Array.from(template.content.childNodes).forEach((node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) {
            const text = node.textContent?.trim();
            if (!text) return;
            const textHtml = escapeHtml(text);
            if (current) {
                current.answer.push(textHtml);
            } else {
                intro.push(textHtml);
            }

            return;
        }

        const element = node;
        const tagName = element.tagName.toLowerCase();

        if (/^h[2-4]$/.test(tagName)) {
            if (current?.question) {
                items.push(current);
            }

            current = {
                question: element.textContent.trim(),
                answer: [],
            };

            return;
        }

        if (current) {
            current.answer.push(element.outerHTML);
        } else {
            intro.push(element.outerHTML);
        }
    });

    if (current?.question) {
        items.push(current);
    }

    return {
        introHtml: intro.join(''),
        items: items
            .filter((item) => item.question)
            .map((item) => ({
                question: item.question,
                answer: item.answer.join('') || '<p>Odpowiedź zostanie uzupełniona przez urząd miasta.</p>',
            })),
    };
}

function renderFaqContent(page) {
    const parsed = parseFaqItems(page?.body);
    const items = parsed.items.length ? parsed.items : fallbackFaqItems();

    return `
        <div class="faq-content" aria-label="Najczęstsze pytania">
            <div class="faq-overview">
                <div>
                    <p class="faq-kicker">FAQ mieszkańca</p>
                    <h2>Najczęstsze pytania w jednym miejscu</h2>
                </div>
                <p>Krótko, konkretnie i w kolejności, w jakiej mieszkaniec zwykle przechodzi przez proces.</p>
            </div>
            ${parsed.introHtml ? `<div class="faq-note">${parsed.introHtml}</div>` : ''}
            <div class="faq-list">
                ${items.map((item, index) => `
                    <article class="faq-item">
                        <span class="faq-number" aria-hidden="true">${String(index + 1).padStart(2, '0')}</span>
                        <div class="faq-copy">
                            <h2>${escapeHtml(item.question)}</h2>
                            <div class="faq-answer">${html(item.answer)}</div>
                        </div>
                    </article>
                `).join('')}
            </div>
        </div>
    `;
}

function renderInfoContent(page) {
    if (page?.slug === 'faq') {
        return renderFaqContent(page);
    }

    return `<div class="panel content-body">${page?.body ? html(page.body) : '<p>Tu pojawią się lokalne zasady, dokumenty, harmonogram i najważniejsze informacje dla mieszkańców.</p>'}</div>`;
}

function infoView() {
    const page = getInfoPageFromPath();
    return `
        <section class="section content-page info-page" aria-labelledby="info-title">
            <p class="eyebrow">Informacje</p>
            <h1 id="info-title">${escapeHtml(page?.title || 'O budżecie obywatelskim')}</h1>
            <div class="info-layout">
                ${renderInfoSubnav(page?.slug)}
                <div class="info-main">
                    ${renderInfoContent(page)}
                    ${scheduleBand('schedule-band-compact')}
                </div>
            </div>
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
    if (path === '/login') return loginView();
    if (path === '/rejestracja') return registerView();
    if (path === '/haslo/reset') return passwordRequestView();
    if (/^\/haslo\/reset\/[^/]+$/.test(path)) return passwordResetView();
    if (path === '/panel') return residentDashboardView();
    if (path === '/moje-projekty') return residentProjectsView();
    if (path === '/moje-projekty/zglos') return residentSubmitView();
    if (/^\/moje-projekty\/\d+\/korekta$/.test(path)) return residentCorrectionView();
    if (path === '/konto') return residentAccountView();
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
    cleanupRevealMotion?.();
    cleanupRevealMotion = null;

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
    cleanupMobileNav?.();
    cleanupMobileNav = null;

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

    const navToggle = document.querySelector('[data-nav-toggle]');
    const navMenu = document.querySelector('[data-nav-menu]');
    const siteHeader = navToggle?.closest('.site-header');
    const closeMobileNav = () => {
        siteHeader?.classList.remove('is-nav-open');
        navToggle?.setAttribute('aria-expanded', 'false');
        closeDropdown();
    };
    const toggleMobileNav = (event) => {
        event.stopPropagation();
        const isOpen = siteHeader?.classList.toggle('is-nav-open') || false;
        navToggle.setAttribute('aria-expanded', String(isOpen));
        if (!isOpen) {
            closeDropdown();
        }
    };
    const closeMobileNavOnOutsideClick = (event) => {
        if (!siteHeader || siteHeader.contains(event.target)) return;
        closeMobileNav();
    };
    const closeMobileNavOnEscape = (event) => {
        if (event.key !== 'Escape') return;
        closeMobileNav();
    };
    const closeMobileNavOnLinkClick = (event) => {
        const link = event.target.closest('a');
        if (!link) return;
        closeMobileNav();
    };

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', toggleMobileNav);
        navMenu.addEventListener('click', closeMobileNavOnLinkClick);
        document.addEventListener('click', closeMobileNavOnOutsideClick);
        document.addEventListener('keydown', closeMobileNavOnEscape);
        cleanupMobileNav = () => {
            navToggle.removeEventListener('click', toggleMobileNav);
            navMenu.removeEventListener('click', closeMobileNavOnLinkClick);
            document.removeEventListener('click', closeMobileNavOnOutsideClick);
            document.removeEventListener('keydown', closeMobileNavOnEscape);
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

    const budgetInputs = Array.from(document.querySelectorAll('[data-budget-input]'));
    const budgetTotal = document.querySelector('[data-budget-total]');
    if (budgetInputs.length && budgetTotal) {
        const formatCurrency = (value) => new Intl.NumberFormat('pl-PL', {
            style: 'currency',
            currency: 'PLN',
            maximumFractionDigits: 0,
        }).format(value);
        const updateBudgetTotal = () => {
            const total = budgetInputs.reduce((sum, input) => sum + (Number(String(input.value).replace(',', '.')) || 0), 0);
            budgetTotal.textContent = formatCurrency(total);
        };
        budgetInputs.forEach((input) => input.addEventListener('input', updateBudgetTotal));
        updateBudgetTotal();
    }

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

    bindHomeMotion();
}

function bindHomeMotion() {
    if (currentPath() !== '/') return;

    const revealItems = Array.from(document.querySelectorAll([
        '.news-strip',
        '.knowledge-card',
        '.city-personality',
        '.action-band-inner > *',
        '.cost-grid > *',
        '.price-board',
        '.map-band',
        '.schedule-band > .eyebrow',
        '.schedule-band > h2',
        '.schedule-band > .lead',
        '.stage',
        '.final-cta',
    ].join(',')));

    if (!revealItems.length) return;

    revealItems.forEach((item, index) => {
        item.classList.add('home-reveal');
        item.style.setProperty('--motion-order', String(index % 5));
    });

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -12% 0px',
        threshold: 0.18,
    });

    revealItems.forEach((item) => observer.observe(item));
    cleanupRevealMotion = () => observer.disconnect();
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

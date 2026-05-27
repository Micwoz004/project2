(function () {
  const translations = {
    pl: {
      navHome: "Start",
      navProjects: "Projekty",
      navInfo: "Informacje",
      navSubmit: "Zglos projekt",
      searchPlaceholder: "Szukaj po nazwie, osiedlu lub kategorii",
      noResults: "Nie znaleziono projektow dla tych filtrow.",
      saved: "Zapisano wybor w prototypie.",
      languageChanged: "Zmieniono jezyk interfejsu.",
      contrastOn: "Wlaczono wysoki kontrast.",
      contrastOff: "Wylaczono wysoki kontrast.",
      fontLarge: "Wlaczono wiekszy tekst.",
      fontBase: "Przywrocono standardowy rozmiar tekstu."
    },
    uk: {
      navHome: "Головна",
      navProjects: "Проєкти",
      navInfo: "Інформація",
      navSubmit: "Подати проєкт",
      searchPlaceholder: "Шукайте за назвою, районом або категорією",
      noResults: "Немає проєктів для вибраних фільтрів.",
      saved: "Вибір збережено в прототипі.",
      languageChanged: "Мову інтерфейсу змінено.",
      contrastOn: "Увімкнено високий контраст.",
      contrastOff: "Вимкнено високий контраст.",
      fontLarge: "Увімкнено більший текст.",
      fontBase: "Повернено стандартний розмір тексту."
    },
    en: {
      navHome: "Home",
      navProjects: "Projects",
      navInfo: "Information",
      navSubmit: "Submit project",
      searchPlaceholder: "Search by name, district, or category",
      noResults: "No projects match these filters.",
      saved: "Choice saved in the prototype.",
      languageChanged: "Interface language changed.",
      contrastOn: "High contrast enabled.",
      contrastOff: "High contrast disabled.",
      fontLarge: "Larger text enabled.",
      fontBase: "Standard text size restored."
    },
    de: {
      navHome: "Start",
      navProjects: "Projekte",
      navInfo: "Informationen",
      navSubmit: "Projekt einreichen",
      searchPlaceholder: "Nach Name, Bezirk oder Kategorie suchen",
      noResults: "Keine Projekte fur diese Filter gefunden.",
      saved: "Auswahl im Prototyp gespeichert.",
      languageChanged: "Sprache der Oberflache geandert.",
      contrastOn: "Hoher Kontrast aktiviert.",
      contrastOff: "Hoher Kontrast deaktiviert.",
      fontLarge: "Grosserer Text aktiviert.",
      fontBase: "Standard-Textgrosse wiederhergestellt."
    }
  };

  const body = document.body;
  const toast = document.querySelector("[data-toast]");

  function showToast(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add("is-visible");
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => {
      toast.classList.remove("is-visible");
    }, 2200);
  }

  function applyLanguage(lang) {
    const copy = translations[lang] || translations.pl;
    document.documentElement.lang = lang;
    document.querySelectorAll("[data-i18n]").forEach((node) => {
      const key = node.getAttribute("data-i18n");
      if (copy[key]) node.textContent = copy[key];
    });
    document.querySelectorAll("[data-i18n-placeholder]").forEach((node) => {
      const key = node.getAttribute("data-i18n-placeholder");
      if (copy[key]) node.setAttribute("placeholder", copy[key]);
    });
    document.querySelectorAll("[data-lang]").forEach((button) => {
      button.setAttribute("aria-pressed", String(button.dataset.lang === lang));
    });
    try {
      localStorage.setItem("bo:lang", lang);
    } catch (_) {}
  }

  function initLanguage() {
    let stored = "pl";
    try {
      stored = localStorage.getItem("bo:lang") || "pl";
    } catch (_) {}
    applyLanguage(stored);
    document.querySelectorAll("[data-lang]").forEach((button) => {
      button.addEventListener("click", () => {
        applyLanguage(button.dataset.lang);
        showToast((translations[button.dataset.lang] || translations.pl).languageChanged);
      });
    });
  }

  function initA11yToggles() {
    const contrast = document.querySelector("[data-contrast-toggle]");
    const font = document.querySelector("[data-font-toggle]");
    if (contrast) {
      contrast.addEventListener("click", () => {
        const next = body.dataset.contrast === "high" ? "base" : "high";
        body.dataset.contrast = next;
        contrast.setAttribute("aria-pressed", String(next === "high"));
        const lang = document.documentElement.lang || "pl";
        showToast(next === "high" ? translations[lang].contrastOn : translations[lang].contrastOff);
      });
    }
    if (font) {
      font.addEventListener("click", () => {
        const next = body.dataset.font === "large" ? "base" : "large";
        body.dataset.font = next;
        font.setAttribute("aria-pressed", String(next === "large"));
        const lang = document.documentElement.lang || "pl";
        showToast(next === "large" ? translations[lang].fontLarge : translations[lang].fontBase);
      });
    }
  }

  function initProjectFilters() {
    const form = document.querySelector("[data-project-filters]");
    if (!form) return;
    const cards = Array.from(document.querySelectorAll("[data-project-card]"));
    const resultCount = document.querySelector("[data-result-count]");
    const empty = document.querySelector("[data-empty]");

    function normalize(value) {
      return String(value || "").toLocaleLowerCase("pl-PL");
    }

    function filter() {
      const data = new FormData(form);
      const query = normalize(data.get("query"));
      const category = data.get("category");
      const status = data.get("status");
      let visible = 0;
      cards.forEach((card) => {
        const haystack = normalize(card.dataset.search);
        const matchQuery = !query || haystack.includes(query);
        const matchCategory = !category || card.dataset.category === category;
        const matchStatus = !status || card.dataset.status === status;
        const show = matchQuery && matchCategory && matchStatus;
        card.classList.toggle("is-hidden", !show);
        if (show) visible += 1;
      });
      if (resultCount) resultCount.textContent = String(visible);
      if (empty) empty.hidden = visible !== 0;
    }

    form.addEventListener("input", filter);
    form.addEventListener("reset", () => setTimeout(filter, 0));
    filter();
  }

  function initTabs() {
    const tabRows = document.querySelectorAll("[data-tabs]");
    tabRows.forEach((row) => {
      const buttons = Array.from(row.querySelectorAll("[role='tab']"));
      const panels = buttons
        .map((button) => document.getElementById(button.getAttribute("aria-controls")))
        .filter(Boolean);
      buttons.forEach((button) => {
        button.addEventListener("click", () => {
          buttons.forEach((item) => {
            item.setAttribute("aria-selected", String(item === button));
            item.setAttribute("aria-pressed", String(item === button));
          });
          panels.forEach((panel) => {
            panel.hidden = panel.id !== button.getAttribute("aria-controls");
          });
        });
      });
    });
  }

  function initAccordions() {
    document.querySelectorAll("[data-accordion-trigger]").forEach((button) => {
      button.addEventListener("click", () => {
        const panel = document.getElementById(button.getAttribute("aria-controls"));
        if (!panel) return;
        const expanded = button.getAttribute("aria-expanded") === "true";
        button.setAttribute("aria-expanded", String(!expanded));
        panel.hidden = expanded;
      });
    });
  }

  function initActions() {
    document.querySelectorAll("[data-save-action]").forEach((button) => {
      button.addEventListener("click", () => {
        const lang = document.documentElement.lang || "pl";
        button.setAttribute("aria-pressed", "true");
        showToast(translations[lang].saved);
      });
    });
    document.querySelectorAll("[data-submit-demo]").forEach((form) => {
      form.addEventListener("submit", (event) => {
        event.preventDefault();
        const lang = document.documentElement.lang || "pl";
        const required = Array.from(form.querySelectorAll("[required]"));
        const invalid = required.find((field) => !field.value.trim());
        if (invalid) {
          invalid.focus();
          return;
        }
        showToast(translations[lang].saved);
        form.reset();
      });
    });
  }

  initLanguage();
  initA11yToggles();
  initProjectFilters();
  initTabs();
  initAccordions();
  initActions();
})();

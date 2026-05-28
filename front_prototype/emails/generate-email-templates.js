const fs = require("fs");
const path = require("path");

const outDir = __dirname;

const palette = {
  bg: "#f5f5f2",
  surface: "#ffffff",
  fg: "#262626",
  muted: "#60605d",
  border: "#e1e1dc",
  green: "#168e45",
  greenDark: "#0f7838",
  yellow: "#f4c600",
  red: "#d6262e",
  dark: "#262626",
  softGreen: "#e7f6ed",
  softYellow: "#fff6bf",
  softRed: "#ffe8e9"
};

const templates = [
  {
    file: "resident-email-verification.html",
    audience: "Mieszkaniec",
    status: "Weryfikacja",
    accent: palette.green,
    soft: palette.softGreen,
    subject: "Potwierdz adres e-mail w serwisie Budzet Obywatelski",
    preheader: "Kliknij przycisk i aktywuj konto mieszkanca.",
    title: "Potwierdz adres e-mail",
    intro: "Czesc {{residentName}}, aktywuj konto w serwisie Budzet Obywatelski dla {{cityName}}, aby zglaszac projekty, sledzic ich status i brac udzial w glosowaniu.",
    cta: "Potwierdz e-mail",
    ctaText: "#ffffff",
    urlVar: "{{verificationUrl}}",
    facts: [
      ["Konto", "{{residentEmail}}"],
      ["Waznosc linku", "24 godziny"],
      ["Serwis", "Budzet Obywatelski {{cityName}}"]
    ],
    note: "Jesli to nie Ty zakladales konto, zignoruj te wiadomosc. Konto nie zostanie aktywowane bez potwierdzenia adresu e-mail."
  },
  {
    file: "resident-password-reset.html",
    audience: "Mieszkaniec",
    status: "Bezpieczenstwo",
    accent: palette.red,
    soft: palette.softRed,
    subject: "Ustaw nowe haslo do konta mieszkanca",
    preheader: "Otrzymalismy prosbe o reset hasla do Twojego konta.",
    title: "Ustaw nowe haslo",
    intro: "Otrzymalismy prosbe o ustawienie nowego hasla do konta w serwisie Budzet Obywatelski. Uzyj ponizszego przycisku, aby bezpiecznie dokonczyc proces.",
    cta: "Resetuj haslo",
    ctaText: "#ffffff",
    urlVar: "{{resetPasswordUrl}}",
    facts: [
      ["Konto", "{{residentEmail}}"],
      ["Waznosc linku", "30 minut"],
      ["Adres IP", "{{requestIp}}"]
    ],
    note: "Jesli nie prosiles o zmiane hasla, zignoruj wiadomosc. Aktualne haslo pozostanie bez zmian."
  },
  {
    file: "resident-project-submitted.html",
    audience: "Mieszkaniec",
    status: "Projekt przyjety",
    accent: palette.green,
    soft: palette.softGreen,
    subject: "Twoj projekt zostal przyjety do weryfikacji",
    preheader: "Zgloszenie projektu trafilo do urzedu i czeka na sprawdzenie.",
    title: "Projekt jest juz w systemie",
    intro: "Dziekujemy za zgloszenie projektu. Wniosek zostal zapisany i przekazany do weryfikacji formalnej przez zespol Budzetu Obywatelskiego.",
    cta: "Sprawdz status projektu",
    ctaText: "#ffffff",
    urlVar: "{{projectUrl}}",
    facts: [
      ["Numer projektu", "{{projectNumber}}"],
      ["Tytul", "{{projectTitle}}"],
      ["Status", "Weryfikacja formalna"]
    ],
    note: "Jesli urzednik bedzie potrzebowal uzupelnienia informacji, otrzymasz osobna wiadomosc oraz powiadomienie na koncie mieszkanca."
  },
  {
    file: "resident-project-published.html",
    audience: "Mieszkaniec",
    status: "Opublikowany",
    accent: palette.green,
    soft: palette.softGreen,
    subject: "Twoj projekt zostal opublikowany",
    preheader: "Projekt jest widoczny na publicznej liscie projektow.",
    title: "Projekt zostal opublikowany",
    intro: "Twoj projekt przeszedl weryfikacje i jest juz dostepny na publicznej liscie projektow. Mieszkancy moga zapoznac sie z opisem, kosztem i lokalizacja.",
    cta: "Zobacz projekt",
    ctaText: "#ffffff",
    urlVar: "{{publicProjectUrl}}",
    facts: [
      ["Numer projektu", "{{projectNumber}}"],
      ["Kategoria", "{{projectCategory}}"],
      ["Szacunkowy koszt", "{{projectBudget}}"]
    ],
    note: "Przed rozpoczeciem glosowania mozesz udostepnic link do projektu i zachecic mieszkancow do zapoznania sie ze szczegolami."
  },
  {
    file: "resident-project-needs-correction.html",
    audience: "Mieszkaniec",
    status: "Wymaga uzupelnienia",
    accent: palette.yellow,
    soft: palette.softYellow,
    subject: "Uzupelnij informacje w zgloszonym projekcie",
    preheader: "Urzednik poprosil o doprecyzowanie projektu.",
    title: "Projekt wymaga uzupelnienia",
    intro: "Podczas weryfikacji projektu wykryto informacje, ktore wymagaja doprecyzowania. Wejdz do panelu mieszkanca i uzupelnij wskazane pola.",
    cta: "Uzupelnij projekt",
    ctaText: palette.dark,
    urlVar: "{{editProjectUrl}}",
    facts: [
      ["Numer projektu", "{{projectNumber}}"],
      ["Termin uzupelnienia", "{{correctionDeadline}}"],
      ["Powod", "{{correctionReason}}"]
    ],
    note: "Brak uzupelnienia w terminie moze spowodowac pozostawienie projektu bez dalszego procedowania."
  },
  {
    file: "resident-voting-started.html",
    audience: "Mieszkaniec",
    status: "Glosowanie",
    accent: palette.green,
    soft: palette.softGreen,
    subject: "Ruszylo glosowanie w Budzecie Obywatelskim",
    preheader: "Wybierz projekty, ktore chcesz poprzeć w swoim miescie.",
    title: "Glosowanie jest otwarte",
    intro: "Mozesz juz oddac glos na projekty w Budzecie Obywatelskim. Sprawdz liste, wybierz inicjatywy wazne dla Twojej okolicy i zatwierdz glos online.",
    cta: "Przejdz do glosowania",
    ctaText: "#ffffff",
    urlVar: "{{votingUrl}}",
    facts: [
      ["Start", "{{votingStartDate}}"],
      ["Koniec", "{{votingEndDate}}"],
      ["Uprawniony mieszkaniec", "{{residentName}}"]
    ],
    note: "Po oddaniu glosu otrzymasz potwierdzenie. Jezeli potrzebujesz pomocy, skorzystaj z informacji kontaktowych w stopce wiadomosci."
  },
  {
    file: "official-new-project.html",
    audience: "Urzednik",
    status: "Nowy projekt",
    accent: palette.red,
    soft: palette.softRed,
    subject: "Nowy projekt czeka na weryfikacje",
    preheader: "W panelu urzednika pojawilo sie nowe zgloszenie.",
    title: "Nowy projekt do weryfikacji",
    intro: "W systemie pojawil sie nowy projekt mieszkanca. Sprawdz dane formalne, budzet, lokalizacje oraz zalaczniki i przypisz dalszy etap obslugi.",
    cta: "Otworz projekt",
    ctaText: "#ffffff",
    urlVar: "{{officialProjectUrl}}",
    facts: [
      ["Numer projektu", "{{projectNumber}}"],
      ["Autor", "{{residentName}}"],
      ["Kategoria", "{{projectCategory}}"]
    ],
    note: "Wiadomosc jest kierowana do zespolu obslugujacego Budzet Obywatelski. Nie przesylaj danych autora poza systemem."
  },
  {
    file: "official-verification-deadline.html",
    audience: "Urzednik",
    status: "Termin",
    accent: palette.yellow,
    soft: palette.softYellow,
    subject: "Zbliza sie termin weryfikacji projektu",
    preheader: "Projekt wymaga decyzji przed koncem etapu.",
    title: "Termin weryfikacji jest blisko",
    intro: "Projekt pozostaje w statusie weryfikacji, a termin obslugi etapu zbliza sie do konca. Zweryfikuj dane lub przekaz projekt do uzupelnienia.",
    cta: "Przejdz do weryfikacji",
    ctaText: palette.dark,
    urlVar: "{{verificationQueueUrl}}",
    facts: [
      ["Numer projektu", "{{projectNumber}}"],
      ["Pozostalo", "{{daysLeft}} dni"],
      ["Aktualny status", "{{projectStatus}}"]
    ],
    note: "To przypomnienie ma pomoc utrzymac terminowosc procesu i transparentny status dla mieszkanca."
  }
];

function emailDocument(template) {
  return `<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>${template.subject}</title>
  <style>
    @media (max-width: 680px) {
      .email-wrap { width: 100% !important; }
      .email-pad { padding-left: 22px !important; padding-right: 22px !important; }
      .email-title { font-size: 34px !important; line-height: 1.04 !important; }
      .email-button { display: block !important; width: 100% !important; text-align: center !important; }
      .fact-cell { display: block !important; width: 100% !important; border-right: 0 !important; border-bottom: 1px solid ${palette.border} !important; }
      .fact-cell:last-child { border-bottom: 0 !important; }
    }
  </style>
</head>
<body style="margin:0; padding:0; background:${palette.bg}; color:${palette.fg}; font-family:'Source Sans 3','Segoe UI',Arial,sans-serif;">
  <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">${template.preheader}</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:${palette.bg}; border-collapse:collapse;">
    <tr>
      <td align="center" style="padding:32px 14px 0;">
        <table role="presentation" class="email-wrap" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; background:${palette.surface}; border-collapse:separate; border-spacing:0; border-radius:26px 26px 0 0; overflow:hidden; box-shadow:0 20px 70px rgba(38,38,38,.12);">
          <tr>
            <td class="email-pad" style="padding:30px 38px 22px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <div style="font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:26px; line-height:1; font-weight:800; letter-spacing:.01em; color:${palette.fg};">
                      <span style="display:inline-block; width:42px; height:42px; margin-right:10px; border-radius:14px; background:${palette.green}; color:#fff; text-align:center; line-height:42px;">BO</span>
                      {{cityName}}
                    </div>
                  </td>
                  <td align="right" style="vertical-align:middle;">
                    <span style="display:inline-block; padding:8px 12px; border-radius:999px; background:${template.soft}; color:${palette.fg}; font-size:12px; line-height:1; font-weight:800; text-transform:uppercase; letter-spacing:.08em;">${template.audience}</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0 38px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border-radius:22px; background:${palette.dark};">
                <tr>
                  <td background="../assets/civic-projects-illustration.png" style="height:138px; background-color:${palette.dark}; background-image:linear-gradient(90deg, rgba(22,142,69,.94), rgba(244,198,0,.82)), url('../assets/civic-projects-illustration.png'); background-size:cover; background-position:center;">
                    <div style="padding:26px 28px; color:#fff;">
                      <div style="font-size:12px; line-height:1; font-weight:900; text-transform:uppercase; letter-spacing:.12em;">Budzet Obywatelski</div>
                      <div style="margin-top:12px; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:36px; line-height:1; font-weight:800;">${template.status}</div>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class="email-pad" style="padding:36px 46px 16px;">
              <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:${palette.fg};">${template.title}</h1>
              <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:${palette.fg};">${template.intro}</p>
            </td>
          </tr>
          <tr>
            <td class="email-pad" style="padding:12px 46px 24px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid ${palette.border}; border-radius:18px; overflow:hidden; border-collapse:separate; border-spacing:0;">
                <tr>
                  ${template.facts.map((fact, index) => `<td class="fact-cell" width="33.33%" style="padding:16px 18px; vertical-align:top; ${index < template.facts.length - 1 ? `border-right:1px solid ${palette.border};` : ""}">
                    <div style="font-size:11px; line-height:1.1; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:${palette.muted};">${fact[0]}</div>
                    <div style="margin-top:7px; font-size:16px; line-height:1.25; font-weight:800; color:${palette.fg};">${fact[1]}</div>
                  </td>`).join("")}
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" class="email-pad" style="padding:8px 46px 30px;">
              <a class="email-button" href="${template.urlVar}" style="display:inline-block; background:${template.accent}; color:${template.ctaText}; text-decoration:none; border-radius:999px; padding:16px 28px; font-size:18px; line-height:1; font-weight:800; box-shadow:0 10px 24px rgba(0,0,0,.16);">${template.cta}</a>
              <p style="margin:18px 0 0; font-size:13px; line-height:1.55; color:${palette.muted};">Jesli przycisk nie dziala, skopiuj i wklej ten adres w przegladarce:<br><span style="color:${palette.green}; word-break:break-all;">${template.urlVar}</span></p>
            </td>
          </tr>
          <tr>
            <td class="email-pad" style="padding:0 46px 42px;">
              <div style="border-left:5px solid ${template.accent}; background:${template.soft}; border-radius:0 18px 18px 0; padding:18px 20px; font-size:15px; line-height:1.55; color:${palette.fg};">${template.note}</div>
            </td>
          </tr>
        </table>
        <table role="presentation" class="email-wrap" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; border-collapse:collapse;">
          <tr>
            <td style="height:5px; background:${palette.red};"></td>
          </tr>
          <tr>
            <td align="center" style="background:${palette.dark}; padding:28px 34px 34px; color:#d9d9d3; font-size:13px; line-height:1.55;">
              <strong style="color:#fff;">Budzet Obywatelski {{cityName}}</strong><br>
              Otrzymujesz te wiadomosc, poniewaz korzystasz z serwisu Budzetu Obywatelskiego.<br>
              Kontakt: <a href="mailto:{{supportEmail}}" style="color:${palette.yellow}; text-decoration:underline;">{{supportEmail}}</a> · {{supportPhone}}<br>
              <a href="{{accessibilityUrl}}" style="color:#ffffff; text-decoration:underline;">Deklaracja dostepnosci</a> · <a href="{{privacyUrl}}" style="color:#ffffff; text-decoration:underline;">Prywatnosc</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
`;
}

function galleryDocument() {
  const cards = templates.map((template) => `
      <article class="mail-card">
        <div>
          <span class="audience">${template.audience}</span>
          <h2>${template.title}</h2>
          <p>${template.subject}</p>
        </div>
        <a href="./${template.file}">Otworz szablon</a>
      </article>`).join("");

  return `<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Szablony maili - Budzet Obywatelski</title>
  <style>
    :root {
      --bg: ${palette.bg};
      --surface: #fff;
      --fg: ${palette.fg};
      --muted: ${palette.muted};
      --border: ${palette.border};
      --green: ${palette.green};
      --yellow: ${palette.yellow};
      --red: ${palette.red};
      --dark: ${palette.dark};
      --font-display: "Barlow Condensed", "Arial Narrow", "Aptos Display", system-ui, sans-serif;
      --font-body: "Source Sans 3", "Aptos", "Segoe UI", -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: var(--bg);
      color: var(--fg);
      font-family: var(--font-body);
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }
    .hero {
      background: linear-gradient(135deg, rgba(22,142,69,.95), rgba(38,38,38,.82)), url("../assets/civic-hero-city.png");
      background-size: cover;
      background-position: center;
      color: white;
      padding: 76px max(28px, calc((100vw - 1180px) / 2)) 64px;
    }
    .hero span {
      display: inline-flex;
      border-radius: 999px;
      background: var(--yellow);
      color: var(--dark);
      padding: 8px 14px;
      font-size: 13px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: .08em;
    }
    h1 {
      max-width: 780px;
      margin: 20px 0 0;
      font-family: var(--font-display);
      font-size: clamp(48px, 8vw, 92px);
      line-height: .96;
      letter-spacing: 0;
    }
    .lead {
      max-width: 760px;
      margin: 22px 0 0;
      font-size: 21px;
      line-height: 1.48;
    }
    main {
      max-width: 1180px;
      margin: 0 auto;
      padding: 42px 28px 72px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }
    .mail-card {
      min-height: 196px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 22px;
      border: 1px solid var(--border);
      border-radius: 24px;
      background: var(--surface);
      padding: 26px;
      box-shadow: 0 16px 42px rgba(38,38,38,.08);
    }
    .audience {
      display: inline-flex;
      width: fit-content;
      border-radius: 999px;
      background: #e7f6ed;
      color: var(--green);
      padding: 7px 11px;
      font-size: 12px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: .08em;
    }
    h2 {
      margin: 14px 0 8px;
      font-family: var(--font-display);
      font-size: 36px;
      line-height: 1;
    }
    p {
      margin: 0;
      color: var(--muted);
      font-size: 17px;
      line-height: 1.45;
    }
    a {
      width: fit-content;
      border-radius: 999px;
      background: var(--green);
      color: white;
      padding: 12px 18px;
      font-weight: 800;
      text-decoration: none;
    }
    @media (max-width: 760px) {
      .grid { grid-template-columns: 1fr; }
      .hero { padding-top: 54px; }
    }
  </style>
</head>
<body>
  <header class="hero">
    <span>Pakiet komunikacji</span>
    <h1>Szablony maili Budzetu Obywatelskiego</h1>
    <p class="lead">Zestaw transakcyjnych wiadomosci dla mieszkancow i urzednikow: aktywacja konta, reset hasla, statusy projektow, glosowanie oraz kolejka weryfikacji.</p>
  </header>
  <main>
    <section class="grid" aria-label="Lista szablonow maili">
${cards}
    </section>
  </main>
</body>
</html>
`;
}

function polishText(html) {
  const replacements = [
    ["Budzetu", "Budżetu"],
    ["Budzet", "Budżet"],
    ["budzetu", "budżetu"],
    ["Czesc", "Cześć"],
    ["Potwierdz", "Potwierdź"],
    ["adres e-mail", "adres e-mail"],
    ["mieszkanca", "mieszkańca"],
    ["Mieszkaniec", "Mieszkaniec"],
    ["Bezpieczenstwo", "Bezpieczeństwo"],
    ["haslo", "hasło"],
    ["Otrzymalismy", "Otrzymaliśmy"],
    ["otrzymalismy", "otrzymaliśmy"],
    ["prosbe", "prośbę"],
    ["Uzyj", "Użyj"],
    ["ponizszego", "poniższego"],
    ["bezpiecznie dokonczyc", "bezpiecznie dokończyć"],
    ["Jesli", "Jeśli"],
    ["prosiles", "prosiłeś"],
    ["wiadomosc", "wiadomość"],
    ["zignoruj wiadomosc", "zignoruj wiadomość"],
    ["zgloszenie", "zgłoszenie"],
    ["zgloszenia", "zgłoszenia"],
    ["zgloszonych", "zgłoszonych"],
    ["zglaszac", "zgłaszać"],
    ["Zglos", "Zgłoś"],
    ["Zgloszenie", "Zgłoszenie"],
    ["Dziekujemy", "Dziękujemy"],
    ["zespol", "zespół"],
    ["bedzie", "będzie"],
    ["uzupelnienia", "uzupełnienia"],
    ["Uzupelnij", "Uzupełnij"],
    ["Uzupelnienie", "Uzupełnienie"],
    ["uzupelnij", "uzupełnij"],
    ["uzupelnienia", "uzupełnienia"],
    ["wazne", "ważne"],
    ["Sledzic", "Śledzić"],
    ["sledzic", "śledzić"],
    ["udzial", "udział"],
    ["glosowaniu", "głosowaniu"],
    ["glosowania", "głosowania"],
    ["glosowanie", "głosowanie"],
    ["Glosowanie", "Głosowanie"],
    ["Glosuj", "Głosuj"],
    ["Przejdz", "Przejdź"],
    ["przegladarce", "przeglądarce"],
    ["aktualne haslo", "aktualne hasło"],
    ["Tytul", "Tytuł"],
    ["Szacunkowy koszt", "Szacunkowy koszt"],
    ["weryfikacje", "weryfikację"],
    ["weryfikacji formalnej", "weryfikacji formalnej"],
    ["Urzednik", "Urzędnik"],
    ["urzednik", "urzędnik"],
    ["urzednika", "urzędnika"],
    ["Zbliza", "Zbliża"],
    ["zbliza", "zbliża"],
    ["blisko", "blisko"],
    ["decyzji", "decyzji"],
    ["koncem", "końcem"],
    ["obslugi", "obsługi"],
    ["obslugujacego", "obsługującego"],
    ["Terminowosc", "Terminowość"],
    ["terminowosc", "terminowość"],
    ["transparentny", "transparentny"],
    ["zostal", "został"],
    ["zostalo", "zostało"],
    ["zostanie", "zostanie"],
    ["opublikowany", "opublikowany"],
    ["dostepny", "dostępny"],
    ["liscie", "liście"],
    ["Mieszkancy", "Mieszkańcy"],
    ["opis", "opis"],
    ["lokalizacja", "lokalizacja"],
    ["rozpoczeciem", "rozpoczęciem"],
    ["mozesz", "możesz"],
    ["udostepnic", "udostępnić"],
    ["szczegolami", "szczegółami"],
    ["wykryto", "wykryto"],
    ["wymagaja", "wymagają"],
    ["doprecyzowania", "doprecyzowania"],
    ["wejdz", "wejdź"],
    ["pola", "pola"],
    ["Brak", "Brak"],
    ["moze", "może"],
    ["spowodowac", "spowodować"],
    ["dalszego procedowania", "dalszego procedowania"],
    ["Ruszylo", "Ruszyło"],
    ["Mozesz", "Możesz"],
    ["juz", "już"],
    ["oddac", "oddać"],
    ["glos", "głos"],
    ["inicjatywy", "inicjatywy"],
    ["okolicy", "okolicy"],
    ["zatwierdz", "zatwierdź"],
    ["Uprawniony", "Uprawniony"],
    ["Po oddaniu glosu", "Po oddaniu głosu"],
    ["otrzymasz", "otrzymasz"],
    ["potwierdzenie", "potwierdzenie"],
    ["pomocy", "pomocy"],
    ["skorzystaj", "skorzystaj"],
    ["stopce", "stopce"],
    ["Urzednik", "Urzędnik"],
    ["Urzad", "Urząd"],
    ["Nowy projekt", "Nowy projekt"],
    ["zespół obslugujacy", "zespół obsługujący"],
    ["przesylaj", "przesyłaj"],
    ["poza systemem", "poza systemem"],
    ["Pozostalo", "Pozostało"],
    ["Aktualny status", "Aktualny status"],
    ["Prywatnosc", "Prywatność"],
    ["dostepnosci", "dostępności"],
    ["wiadomosc", "wiadomość"],
    ["wiadomosci", "wiadomości"],
    ["Szablony maili", "Szablony maili"],
    ["Pakiet komunikacji", "Pakiet komunikacji"],
    ["transakcyjnych wiadomosci", "transakcyjnych wiadomości"],
    ["urzednikow", "urzędników"],
    ["aktywizacja", "aktywizacja"],
    ["hasla", "hasła"],
    ["kolejka weryfikacji", "kolejka weryfikacji"],
    ["Otworz", "Otwórz"],
    ["szablon", "szablon"],
    ["Zgłośzenie", "Zgłoszenie"],
    ["trafilo", "trafiło"],
    ["urzedu", "urzędu"],
    ["Sprawdz", "Sprawdź"],
    ["poprosil", "poprosił"],
    ["ktore", "które"],
    ["Wejdz", "Wejdź"],
    ["Powod", "Powód"],
    ["dziala", "działa"],
    ["poniewaz", "ponieważ"],
    ["Otrzymujesz te wiadomość", "Otrzymujesz tę wiadomość"]
    ,["zakladales", "zakładałeś"]
    ,["zignoruj te wiadomość", "zignoruj tę wiadomość"]
    ,["przyjety", "przyjęty"]
    ,["potrzebowal", "potrzebował"]
    ,["osobna", "osobną"]
    ,["ma pomoc utrzymac", "ma pomóc utrzymać"]
  ];

  return replacements.reduce((acc, [from, to]) => acc.split(from).join(to), html);
}

for (const template of templates) {
  fs.writeFileSync(path.join(outDir, template.file), polishText(emailDocument(template)), "utf8");
}
fs.writeFileSync(path.join(outDir, "index.html"), polishText(galleryDocument()), "utf8");

console.log(`Generated ${templates.length + 1} email files in ${outDir}`);

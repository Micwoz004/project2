# Brand spec: miejski budzet obywatelski

Zrodla referencyjne:
- https://bo.bialystok.pl/
- https://czestochowa.budzet-obywatelski.eu/
- Kierunek wizualny: kampanijny serwis miejski inspirowany struktura konkurencji

## Tokeny

```css
:root {
  --bg: oklch(96.9% 0.004 100);
  --surface: oklch(100% 0 0);
  --fg: oklch(26% 0 0);
  --muted: oklch(53% 0 0);
  --border: oklch(89% 0.004 100);
  --accent: oklch(55% 0.15 148);
}
```

## Typografia

- Display: "Barlow Condensed", "Arial Narrow", "Aptos Display", system-ui, sans-serif
- Body: "Source Sans 3", "Aptos", "Segoe UI", -apple-system, BlinkMacSystemFont, system-ui, sans-serif
- Mono: "IBM Plex Mono", "SF Mono", ui-monospace, Menlo, monospace
- Skala: 12 / 14 / 17 / 20 / 26 / 40 / 58 / 84 px
- Zastosowanie: display tylko dla hero, naglowkow, nawigacji i CTA; body dla tresci; mono tylko dla etykiet narzedzi, metadanych i licznikow.

## Obserwacje z referencji

1. Serwisy BO prowadza uzytkownika przez etap procesu: zgloszenie, weryfikacja, lista do glosowania, glosowanie, wyniki.
2. Potrzebne sa stale skroty dostepnosci: przejdz do tresci, zmiana kontrastu, zmiana rozmiaru tekstu, deklaracja dostepnosci.
3. Najwazniejsza akcja powinna byc jednoznaczna i widoczna: "Zglos projekt" albo "Glosuj".
4. Modul miasta powinien byc wymienny: logo, nazwa miasta, budzet, kontakt, dzielnice/osiedla i lokalne ogloszenia.
5. Grafiki powinny byc tematyczne i generyczne: zielen, ulice, place zabaw, biblioteki, rowery, kultura, bez landmarkow jednego miasta.

## Postura layoutu

- Kampania miejska BO: fotograficzny baner, biala pływajaca nawigacja, zielone CTA, zólte akcenty i czerwony pas harmonogramu.
- Układ wzorowany na konkurencji: duzy baner hero, karta aktualnosci, centralny przewodnik, duze ilustracje tematyczne, mapa, harmonogram i ciemny footer.
- Komponenty sa mniej "systemowe": płaskie nowoczesne przyciski, wieksze bloki pelnej szerokosci, wyrazne pasy koloru i mocniejsza hierarchia.
- Komponenty WCAG-first: kontrast, focus-visible, skip links, czytelne etykiety, realne stany formularzy.

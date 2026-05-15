# Liczenie wyników

1. Legacy: `ResultsController`, `PublicReportController`, zapytania na `votes` i `votecards`.
2. Tabele: `votes`, `votecards`, `tasks`, `tasktypes`, `voters`.
3. Dane wejściowe: edycja, statusy kart, obszary, kategorie.
4. Dane zapisywane: docelowo publikowane wyniki i raporty; baseline liczy odczytowo.
5. Statusy: tylko `VoteCard::STATUS_ACCEPTED` wpływa na wynik.
6. Walidacje: filtrowanie po edycji, statusie karty i projektach.
7. Role: publiczny odczyt wyników, administracja do raportów szczegółowych.
8. Edge case: remisy, projekty ukryte, karty weryfikowane, korekty po publikacji.
9. Laravel: `ResultsCalculator`, `/wyniki`.
10. Zgodność: test sumowania tylko ważnych kart, agregacji po obszarach/kategoriach i porównanie z raportem legacy.

## Plan wdrożenia

Status: baseline domenowy rozbudowany.

1. [x] Rozszerzyć kalkulator o obszary i kategorie.
2. Dodać reguły remisów po potwierdzeniu legacy.
3. [x] Dodać publikację wyników zależną od etapu edycji.
4. Zbudować publiczny i administracyjny widok wyników.
5. [x] Pokryć testami accepted/rejected/verifying i agregacje.

## Implementacja Laravel

- `ResultsCalculator::projectTotals()` sumuje punkty projektów wyłącznie z kart `Accepted`.
- `ResultsCalculator::areaTotals()` sumuje punkty po `project_areas` dla tej samej edycji.
- `ResultsCalculator::categoryTotals()` sumuje punkty po podstawowej kategorii projektu.
- `ResultsPublicationService` pozwala publicznie pokazać wyniki tylko w stanie edycji `ResultAnnouncement`.
- `/wyniki` nie liczy ani nie pokazuje punktów przed oknem publikacji wyników.

## Świadome braki na tym etapie

- Reguły remisów wymagają dalszego potwierdzenia z legacy.
- Agregacja po wielu kategoriach z pivotu `category_project` nie jest jeszcze odtworzona, jeśli raport legacy używał wielu kategorii projektu.

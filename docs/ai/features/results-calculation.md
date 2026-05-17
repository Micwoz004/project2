# Liczenie wyników

1. Legacy: `ResultsController`, `PublicReportController`, zapytania na `votes` i `votecards`.
2. Tabele: `votes`, `votecards`, `tasks`, `tasktypes`, `voters`.
3. Dane wejściowe: edycja, statusy kart, obszary, kategorie.
4. Dane zapisywane: snapshot publikacji wyników w `result_publications`, ręczne decyzje remisów w `result_tie_decisions`, raporty generowane z tych samych agregatów.
5. Statusy: tylko `VoteCard::STATUS_ACCEPTED` wpływa na wynik.
6. Walidacje: filtrowanie po edycji, statusie karty i projektach.
7. Role: publiczny odczyt wyników, administracja do raportów szczegółowych.
8. Edge case: remisy, projekty ukryte, karty weryfikowane, korekty po publikacji.
9. Laravel: `ResultsCalculator`, `/wyniki`.
10. Zgodność: test sumowania tylko ważnych kart, agregacji po obszarach/kategoriach i porównanie z raportem legacy.

## Plan wdrożenia

Status: baseline domenowy rozbudowany.

1. [x] Rozszerzyć kalkulator o obszary i kategorie.
2. [x] Dodać deterministyczną kolejność remisów w rankingu: punkty malejąco, `number_drawn`, `project_id`.
3. [x] Dodać publikację wyników zależną od etapu edycji.
4. [x] Zbudować publiczny i administracyjny widok wyników.
5. [x] Pokryć testami accepted/rejected/verifying i agregacje.
6. [x] Dodać jawne wykrywanie remisów wymagających decyzji manualnej.
7. [x] Dodać porównanie wyników liczonych po kategorii głównej projektu i po wielu kategoriach.
8. [x] Dodać domenowy zapis ręcznej decyzji remisu bez automatycznego zgadywania zwycięzcy.
9. [x] Podpiąć decyzję remisu pod panel `/admin/wyniki`, z zachowaniem walidacji w `ResolveResultTieDecisionAction`.
10. [x] Dodać audytowalny snapshot publikowanych wyników z wersjonowaniem per edycja.

## Implementacja Laravel

- `ResultsCalculator::projectTotals()` sumuje punkty projektów wyłącznie z kart `Accepted` i sortuje ranking po punktach malejąco, następnie po `number_drawn` oraz `project_id`.
- `ResultsCalculator::areaTotals()` sumuje punkty po `project_areas` dla tej samej edycji.
- `ResultsCalculator::categoryTotals()` sumuje punkty po kategoriach z pivotu `category_project`, a dla rekordów bez pivotu używa `projects.category_id`.
- `ResultsCalculator::categoryComparisonTotals()` zwraca raport różnic między legacy kategorią główną `projects.category_id` i nowym przypisaniem wielu kategorii z pivotu.
- `ResultsPublicationService` pozwala publicznie pokazać wyniki tylko w stanie edycji `ResultAnnouncement`.
- `ResultTieBreakerService` wykrywa grupy projektów z tą samą liczbą punktów i oznacza je jako wymagające decyzji manualnej; nie wybiera zwycięzcy automatycznie, bo w legacy nie znaleziono takiej reguły.
- `ResolveResultTieDecisionAction` zapisuje ręczną decyzję remisu w `result_tie_decisions`: edycję, stabilny klucz grupy, punkty, listę projektów, zwycięski projekt, operatora, czas decyzji i notatkę. Akcja wymaga `reports.export` albo roli `admin`/`bdo`, sprawdza istnienie aktualnej grupy remisowej i odrzuca projekt spoza grupy.
- `ResultTieBreakerService` dołącza istniejącą decyzję do wykrytej grupy remisowej i wtedy `requires_manual_decision=false`; nadal nie podejmuje decyzji automatycznej.
- `PublishResultSnapshotAction` utrwala publicznie publikowane wyniki w `result_publications`: wersję, operatora, pełny ranking projektów, agregaty obszarów i kategorii, statusy kart, grupy remisowe i różnice kategorii. Akcja wymaga `reports.export` albo roli `admin`/`bdo` oraz aktywnego etapu `ResultAnnouncement`.
- `/wyniki` nie liczy ani nie pokazuje punktów przed oknem publikacji wyników.
- `ResultsDashboardService` buduje administracyjne podsumowanie wyników dla edycji: statusy kart, pełny ranking projektów, top projektów, punkty po obszarach i kategoriach, remisy wymagające decyzji, różnice między kategorią główną i wieloma kategoriami oraz metadane ostatniego snapshotu.
- Filament page `/admin/wyniki` pokazuje administracyjny dashboard wyników dla operatorów z uprawnieniem `results.view`; linki do raportów CSV pozostają za `reports.export`.
- Operator z `reports.export` albo rolą `admin`/`bdo` może z poziomu `/admin/wyniki` wybrać zwycięski projekt dla aktywnej grupy remisowej. Formularz przekazuje stabilny klucz grupy i listę projektów do `ResolveResultTieDecisionAction`, więc UI nie rozstrzyga reguł samodzielnie.
- Operator z `reports.export` albo rolą `admin`/`bdo` może z poziomu `/admin/wyniki` utrwalić snapshot wyników, ale wyłącznie gdy te wyniki są już publicznie publikowalne według harmonogramu edycji.
- `ResultPublicationResource` pokazuje read-only listę utrwalonych snapshotów wyników: edycję, wersję, liczbę punktów/projektów, operatora i czas utrwalenia. Dostęp wymaga `results.view`, `reports.export` albo roli `admin`/`bdo`.

## Świadome braki na tym etapie

- Nie znaleziono w legacy automatycznej procedury wyboru zwycięzcy przy remisie. Nowy system wykrywa remisy i wymaga decyzji manualnej, zachowując deterministyczną kolejność rankingu/raportu.
- Snapshot wyników jest uruchamiany ręcznie przez operatora; automatyczna publikacja cykliczna nie jest implementowana, bo nie wynika wprost z rozpoznanej logiki legacy.

# Raporty i eksporty

1. Legacy: `ReportController`, `PublicReportController`, `ResultsController`, katalog `raporty_sbo`.
2. Tabele: `tasks`, `tasktypes`, `votes`, `votecards`, `voters`, `users`, `taskverification*`.
3. Dane wejściowe: edycja, obszar, status, zakres raportu.
4. Dane zapisywane: pliki eksportów lub odpowiedzi download.
5. Statusy: zależne od typu raportu; wyniki publiczne tylko po etapie publikacji.
6. Walidacje: uprawnienia, zakres danych, anonimizacja PII w publicznych raportach.
7. Role: publiczny użytkownik dla raportów publicznych; administracja dla eksportów szczegółowych.
8. Edge case: raport historyczny, PII, różnice accepted/verifying/rejected.
9. Laravel: moduł `Reports`, `VoteCardReportService`, `PublicResultsCsvExporter`, kolejki dla ciężkich eksportów, `raporty-publiczne`.
10. Zgodność: lista raportów legacy, test liczności statusów/demografii, test CSV bez PII i porównanie kolumn/filtrów.

## Plan wdrożenia

Status: baseline domenowy rozpoczęty.

1. Zinwentaryzować raporty z `ReportController` i `raporty_sbo`.
2. Dla każdego raportu opisać kolumny, filtry i PII.
3. [x] Wdrożyć pierwszy publiczny eksport CSV wyników bez PII.
4. [x] Dodać bazowe raporty bez danych wrażliwych: statusy kart i demografia zaakceptowanych kart.
5. Pokryć testami kolumny i liczności względem fixture legacy.

## Implementacja Laravel

- `VoteCardReportService::statusCounts()` zwraca liczby kart po statusach dla edycji.
- `VoteCardReportService::acceptedVoterDemographics()` zwraca zagregowane płcie i przedziały wieku wyłącznie dla zaakceptowanych kart, bez PESEL, telefonu ani danych osobowych.
- `VoteCardReportService::projectAgeGroupTotals()` odtwarza administracyjny raport wieku z legacy dla grup `16-30`, `31-45`, `46-60`, `61+` per projekt.
- `VoteCardReportService::projectSexTotals()` odtwarza administracyjny raport punktów według płci per projekt.
- `PublicResultsCsvExporter` eksportuje publiczne wyniki CSV z kolumnami `project_id`, `project_number`, `title`, `area`, `points`.
- `/wyniki/export.csv` jest dostępne tylko w oknie publikacji wyników.

## Świadome braki na tym etapie

- Brak administracyjnych eksportów XLSX i kolejek dla dużych raportów.
- Brak pełnej inwentaryzacji plików z `raporty_sbo`.
- Brak raportów administracyjnych z kolumnami 1:1 względem legacy.

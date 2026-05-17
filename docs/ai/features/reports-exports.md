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

1. [x] Zinwentaryzować raporty z `ReportController`, `FuckupController`, `DocumentController` i `raporty_sbo`.
2. [x] Dla każdego raportu opisać kolumny, filtry i PII.
3. [x] Wdrożyć pierwszy publiczny eksport CSV wyników bez PII.
4. [x] Dodać bazowe raporty bez danych wrażliwych: statusy kart i demografia zaakceptowanych kart.
5. [x] Pokryć testami kolumny i liczności względem fixture legacy.
6. [x] Dodać administracyjne endpointy CSV z uprawnieniem `reports.export`.
7. [x] Dodać administracyjne endpointy XLSX generowane z tych samych danych domenowych co CSV.

## Implementacja Laravel

- `VoteCardReportService::statusCounts()` zwraca liczby kart po statusach dla edycji.
- `VoteCardReportService::acceptedVoterDemographics()` zwraca zagregowane płcie i przedziały wieku wyłącznie dla zaakceptowanych kart, bez PESEL, telefonu ani danych osobowych.
- `VoteCardReportService::projectAgeGroupTotals()` odtwarza administracyjny raport wieku z legacy dla grup `16-30`, `31-45`, `46-60`, `61+` per projekt.
- `VoteCardReportService::projectSexTotals()` odtwarza administracyjny raport punktów według płci per projekt.
- `VoteCardReportService::projectCardTypeTotals()` odtwarza administracyjny raport punktów według typu karty: elektroniczna/papierowa.
- `VoteCardReportService::adminVoteCardRows()` odtwarza dane dla legacy `ReportController::actionVoteCardReport`: ID karty, typ, PESEL, imię, nazwisko, oświadczenie zamieszkania, status, uwagi, daty i IP.
- `AdminVoteCardsCsvExporter` eksportuje administracyjny raport kart głosowania z kolumnami legacy; zawiera PII i musi być udostępniany wyłącznie przez uprawnienie `reports.export`/`vote_cards.manage`.
- `ProjectReportService::submittedProjectRows()` i `SubmittedProjectsCsvExporter` odtwarzają awaryjny raport `FuckupController::actionGenerateTaskReport`: numer wniosku, tytuł i data złożenia dla projektów złożonych od `2019-07-07 00:00:00`.
- `ProjectReportService::unsentAdvancedVerificationRows()` i `UnsentAdvancedVerificationsCsvExporter` odtwarzają awaryjny raport `FuckupController::actionGetUnsentVerifications`: projekty w statusach `Submitted`, `FormallyVerified`, `RecommendedWjo`, `RejectedFormally`, `RejectedWjo` z niewysłanymi `taskadvancedverification`.
- `ProjectReportService::projectCorrectionRows()` i `ProjectCorrectionsCsvExporter` odtwarzają awaryjny raport `FuckupController::actionGenerateTaskCorrectionReport`, rozwijając `allowed_fields` do flag kolumn legacy z `TaskCorrection::attributeLabels`.
- `ProjectReportService::projectHistoryRows()` i `ProjectHistoryCsvExporter` odtwarzają awaryjny raport `FuckupController::actionGetTaskHistory` z danych historycznych `versions.data`.
- `ProjectReportService::verificationResultManifestRows()` i `VerificationResultManifestCsvExporter` odtwarzają selekcję paczki `DocumentController::actionGenVerificationResultReport`: projekty poza `WorkingCopy` i `Revoked`, które mają formalną albo wysłane karty merytoryczne/konsultacje.
- `PublicResultsCsvExporter` eksportuje publiczne wyniki CSV z kolumnami `project_id`, `project_number`, `title`, `area`, `points`.
- `CategoryComparisonCsvExporter` eksportuje raport porównujący punkty po legacy kategorii głównej projektu i po wielu kategoriach z `category_project`; raport nie zawiera PII.
- `/wyniki/export.csv` jest dostępne tylko w oknie publikacji wyników.
- `AdminReportController` udostępnia CSV za `auth` i `reports.export`: karty głosowania z PII, projekty złożone, niewysłane weryfikacje jednostek, korekty projektów, historię projektów, manifest wyników weryfikacji i porównanie kategorii.
- `XlsxFromCsvExporter` generuje administracyjne XLSX z tych samych strumieni danych co CSV, bez oddzielnej logiki raportowej. Endpointy `.xlsx` zachowują tę samą bramkę `reports.export` i te same reguły PII co odpowiadające im CSV.
- Filament `/admin/wyniki` udostępnia operatorowi z `results.view` dashboard wyników; skróty do eksportów CSV kart głosowania, kategorii, projektów złożonych, manifestu weryfikacji i historii projektów są widoczne tylko przy `reports.export`.

## Inwentaryzacja legacy

- `ReportController::actionVoteCardReport` eksportuje XLS kart głosowania z PII: ID karty, typ, PESEL, imię, nazwisko, oświadczenie zamieszkania, status, uwagi, daty i IP. Dane są odwzorowane w `adminVoteCardRows`; obecny eksport domenowy generuje CSV z tym samym układem kolumn.
- `ReportController` zawiera raporty wyników per projekt z kolumnami: obszar, numer, tytuł, koszt, płeć, suma, wybrany, pula.
- `ReportController` zawiera raporty wieku per projekt; kolumny wieku są generowane dynamicznie i w aktualnym baseline są odtworzone przez `VoteCardReportService::projectAgeGroupTotals`.
- `ReportController` zawiera raport typu karty per projekt: elektroniczne, papierowe i łączna liczba głosów; baseline jest odtworzony przez `VoteCardReportService::projectCardTypeTotals`.
- `ReportController` ma raporty dzienne/godzinowe głosowania zakomentowane w legacy; traktujemy je jako historyczne, nieaktywne, dopóki nie znajdziemy wywołania w menu.
- `ReportController::actionCsv` używa `ECSVExport` do ogólnego eksportu `raport.csv` z przekazanego data providera.
- `DocumentController::actionGenVerificationResultReport` generuje raport wyników weryfikacji z kart formalnych i merytorycznych.
- Selekcja projektów dla `DocumentController::actionGenVerificationResultReport` jest odwzorowana w manifeście CSV; generowanie PDF zostaje osobnym adapterem infrastrukturalnym.
- `FuckupController` zawiera awaryjne XLS: niewysłane weryfikacje jednostek, lista złożonych projektów, korekty projektu i historia zmian projektu. Lista złożonych projektów, niewysłane weryfikacje jednostek, korekty projektu i historia zmian są odwzorowane jako CSV domenowe.
- Katalog `raporty_sbo` zawiera szablony: `_historia_zmian_projektow.xls`, `_koresponcenje_z_autorem.xlsx`, `_ocena_komisji_odwolawczej.xlsx`, `_ocena_rady_ds_bo.xlsx`, `_propozycja-poprawy.xlsx`, `_tresc-odwolania.xlsx`.

## Kolumny, filtry i PII

### Publiczne wyniki CSV

- Endpoint: `/wyniki/export.csv`.
- Filtr: najnowsza edycja SBO, tylko po rozpoczęciu etapu publikacji wyników.
- Kolumny: `project_id`, `project_number`, `title`, `area`, `points`.
- PII: nie zawiera danych osobowych.

### Administracyjny raport kart głosowania

- Endpoint: `/admin/reports/vote-cards/{budgetEdition}.csv`.
- Legacy: `ReportController::actionVoteCardReport`.
- Filtr: wskazana edycja SBO.
- Kolumny: `ID karty`, `Typ karty`, `PESEL`, `Imie głosującego`, `Nazwisko głosującego`, `Oświadczenie zamieszkania`, `Status`, `Uwagi`, `Data dodania`, `Data modyfikacji`, `IP`.
- PII: zawiera PESEL, imię, nazwisko, IP i potencjalnie dane w uwagach. Dostęp wyłącznie przez `reports.export`.

### Raport złożonych projektów

- Endpoint: `/admin/reports/submitted-projects.csv`.
- Legacy: `FuckupController::actionGenerateTaskReport`.
- Filtr: projekty z `submitted_at >= 2019-07-07 00:00:00`.
- Kolumny: `Numer wniosku`, `Tytuł`, `Data złożenia`.
- PII: nie zawiera danych autora ani głosujących.

### Raport niewysłanych weryfikacji jednostek

- Endpoint: `/admin/reports/unsent-advanced-verifications.csv`.
- Legacy: `FuckupController::actionGetUnsentVerifications`.
- Filtr: niewysłane `advanced_verifications` dla projektów w statusach `Submitted`, `FormallyVerified`, `RecommendedWjo`, `RejectedFormally`, `RejectedWjo`.
- Kolumny: `Numer wniosku`, `Tytuł`, `Nazwa wydziału`, `Nazwa autora`, `Link do projektu`.
- PII: zawiera nazwę użytkownika/autora karty weryfikacji, ale nie zawiera danych wyborców.

### Raport korekt projektów

- Endpoint: `/admin/reports/project-corrections.csv`.
- Legacy: `FuckupController::actionGenerateTaskCorrectionReport`.
- Filtr: wszystkie rekordy `project_corrections`.
- Kolumny: flagi pól `Tytuł`, `Obszary Lokalne`, `Lokalizacja projektu`, `Mapka projektu`, `Cel i uzasadnienie projektu`, `Szczegółowy opis`, `Uzasadnienie projektu`, `Ogólnodostępność projektu`, `Odbiorcy projektu`, `Nieodpłatność projektu`, `Szacunkowe koszty projektu`, flagi załączników, `Kategoria projektu`, `Informacje dla autora`, `Data utworzenia odwołania`, `Termin zakończenia wprowadzania zmian`.
- PII: może zawierać dane osobowe w wolnym polu `Informacje dla autora`, dlatego dostęp pozostaje administracyjny.

### Raport historii zmian projektów

- Endpoint: `/admin/reports/project-history.csv`.
- Legacy: `FuckupController::actionGetTaskHistory`.
- Filtr: wszystkie `project_versions` powiązane z istniejącym projektem.
- Kolumny: `Identyfikator wniosku`, `Numer wniosku`, `Tytuł`, `Kategoria projektu`, `Dzielnica`, `Uzasadnienie kategorii`, `Lokalizacja, miejsce realizacji projektu`, `Cel projektu`, `Szczegółowy opis`, `Odbiorcy projektu`, `Nieodpłatność projektu`, `Status`, `Data zmiany`, `Autor zmiany`.
- PII: może zawierać dane osobowe w treści projektu lub nazwie autora zmiany; dostęp wyłącznie administracyjny.

### Manifest wyników weryfikacji

- Endpoint: `/admin/reports/verification-manifest.csv`.
- Legacy: selekcja `DocumentController::actionGenVerificationResultReport`.
- Filtr: projekty poza `WorkingCopy` i `Revoked`, które mają kartę formalną albo wysłane karty wstępne, końcowe lub konsultacyjne.
- Kolumny: `project_id`, `project_number`, `title`, `formal_present`, `initial_sent_count`, `final_sent_count`, `consultation_sent_count`, `file_name`.
- PII: nie zawiera danych głosujących; tytuł projektu może zawierać dane wpisane przez autora.

### Porównanie kategorii wyników

- Endpoint: `/admin/reports/category-comparison/{budgetEdition}.csv`.
- Filtr: wskazana edycja SBO, punkty z zaakceptowanych kart głosowania.
- Kolumny: `category_id`, `category_name`, `primary_category_points`, `multi_category_points`, `difference`.
- PII: nie zawiera danych osobowych.

### Raporty agregowane w usługach

- `statusCounts`: filtr po edycji SBO, kolumny logiczne `status` i `count`, bez PII.
- `acceptedVoterDemographics`: filtr po edycji SBO i kartach `Accepted`, agregaty płci i wieku, bez PESEL, telefonu i nazwisk.
- `projectAgeGroupTotals`: filtr po edycji SBO i kartach `Accepted`, kolumny projektowe oraz grupy `16-30`, `31-45`, `46-60`, `61+`, bez PII.
- `projectSexTotals`: filtr po edycji SBO i kartach `Accepted`, kolumny projektowe oraz punkty według płci, bez PII.
- `projectCardTypeTotals`: filtr po edycji SBO i kartach `Accepted`, kolumny projektowe oraz punkty z kart elektronicznych/papierowych, bez PII.

## Świadome braki na tym etapie

- Brak kolejek dla dużych raportów; obecny baseline generuje pliki synchronicznie przez kontroler z uprawnieniem.
- Brak pełnych graficznych szablonów XLSX z katalogu `raporty_sbo`; obecny baseline udostępnia XLSX tabelaryczne z tymi samymi danymi domenowymi co CSV.

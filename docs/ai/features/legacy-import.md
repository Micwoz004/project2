# Import legacy

1. Legacy: `ImporterController`, `db/*.sql`, dump `sbo2025_prod.sql`.
2. Tabele: wszystkie tabele domenowe z dumpa; priorytet: edycje, obszary, projekty, koszty, pliki, głosy, role.
3. Dane wejściowe: dump MySQL albo staging MySQL `legacy-mysql`.
4. Dane zapisywane: rekordy PostgreSQL z `legacy_id`, statystyki importu.
5. Statusy: mapowanie statusów projektu i kart bez zmiany wartości.
6. Walidacje: kompletność relacji, brak PII w logach, powtarzalność importu.
7. Role: operator migracji/administrator.
8. Edge case: brakujące FK w danych historycznych, rekordy osierocone, kodowanie znaków, duże pliki.
9. Laravel: `legacy_import_batches`, `LegacyFixtureImportService`, komendy Artisan i transakcje per moduł.
10. Zgodność: fixture z wycinkiem dumpa, liczność rekordów, mapowanie `legacy_id`.

## Plan wdrożenia

Status: baseline fixture, import ze staging MySQL i parser liczności surowego dumpa SQL zaimplementowane.

1. [x] Przygotować staging MySQL z profilem `legacy-import`.
2. [x] Dodać komendę Artisan importującą znormalizowany fixture w kolejności zależności.
3. [x] Zapisywać statystyki w `legacy_import_batches`.
4. [x] Dodać fixture z małym wycinkiem dumpa do testów.
5. [x] Porównać liczność, `legacy_id`, statusy i relacje dla baseline modułów.
6. [x] Dodać komendę importującą bezpośrednio z połączenia MySQL/staging przez istniejące mapowania domenowe.
7. [x] Dodać komendę audytu liczności po imporcie staging MySQL względem tabel docelowych.
8. [x] Dodać parser surowego pliku `.sql` do audytu liczności bez staging MySQL.

## Implementacja Laravel

- `LegacyFixtureImportService` importuje podstawowy wycinek danych w transakcji: `taskgroups`, `settings`, `pages`, `statuses`, `activations`, `pesel`, `verification`, `tasktypes`, `categories`, `tasks`, `logs`, `taskscategories`, `taskcosts`, `files`, `filesprivate`, `cocreators`, `taskverification`, `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation`, `detailedverification`, `locationverification`, `verificationversion`, `taskadvancedverification`, `prerecommendations`, `recommendationswjo`, `tasksinitialverification`, `tasksdepartments`, `coordinatorassignment`, `verifierassignment`, `taskdepartmentassignment`, `verificationpressure`, `zkvotes`, `atvotes`, `otvotes`, `atotvotesrejection`, `taskappealagainstdecision`, `correspondence`, `taskcomments`, `comments`, `notification`, `maillogs`, `taskcorrection`, `taskchangessuggestion`, `versions`, `newverification`, `votingtokens`, `voters`, `smslogs`, `votecards`, `votes`.
- `sbo:legacy-import {path} {--source=}` czyta znormalizowany JSON, waliduje kształt na granicy systemu, uruchamia `LegacyFixtureImportService` i zapisuje batch ze statystykami bez logowania payloadu ani PII.
- `sbo:legacy-import-mysql {--connection=legacy_mysql} {--source=legacy-mysql} {--guard=web}` czyta tabele legacy z połączenia skonfigurowanego w Laravel, normalizuje różnice nazw kolumn i uruchamia kolejno import użytkowników/departamentów, domeny oraz RBAC.
- `sbo:legacy-import-counts {--connection=legacy_mysql} {--fail-on-mismatch} {--json}` porównuje liczności bezpośrednio mapowanych tabel legacy ze staging MySQL z docelowymi tabelami PostgreSQL po imporcie; konsolidacje takie jak `files/filesprivate`, `zkvotes/otvotes/atvotes`, rekomendacje i zakresy departamentów są rozdzielane filtrami docelowymi.
- `sbo:legacy-dump-counts {path} {--compare-target} {--fail-on-mismatch} {--json}` strumieniowo liczy rekordy z `INSERT INTO` w surowym dumpie MySQL bez odtwarzania staging DB i bez logowania wartości rekordów. Tryb `--compare-target` używa tych samych mapowań liczności co `sbo:legacy-import-counts`.
- `LegacySqlDumpTableCounter` rozpoznaje `CREATE TABLE`, wielowierszowe `INSERT INTO ... VALUES` oraz nawiasy/średniki wewnątrz stringów, żeby liczność nie zależała od pełnego parsowania PII z payloadu.
- Parser został uruchomiony na `sbo2025_prod.sql`: rozpoznał 61 tabel i 602134 rekordy z `INSERT INTO`. Wynik zawiera wyłącznie liczności tabel, bez wartości rekordów.
- `legacy_mysql` jest osobnym połączeniem DB skonfigurowanym przez `LEGACY_DB_*`, żeby staging dumpa nie mieszał się z docelowym PostgreSQL.
- `LegacyMysqlSourceReader` normalizuje znane różnice dumpa MySQL: `users.firstname/lastname`, `users.houseno/homeno/postcode`, `users.name`, `tasktypes.nameshortcut` oraz analogiczne pola współautorów.
- `LegacyUserImportService` importuje `departments` i `users`.
- Import jest idempotentny po `legacy_id` przez `updateOrCreate`.
- Ustawienia aplikacji zachowują surową wartość z legacy `settings.value`, także jeśli jest to serializowany format Yii/PHP; typizacja ustawień będzie osobnym etapem po potwierdzeniu wszystkich kluczy z dumpa.
- Strony procesu głosowania zachowują `pages.symbol` i HTML z `pages.body` per edycja SBO.
- Słownik nazw statusów `statuses` trafia do `project_status_labels`, zachowując możliwość porównania etykiet admina z legacy.
- Tokeny `activations` trafiają do `user_activation_tokens` z typem aktywacji konta, aktywacji SMS albo resetu hasła; hash nie jest logowany.
- Rejestry `pesel` i `verification` są importowane jako osobne tabele legacy dla administracji PESEL i historycznej whitelisty autentyczności PESEL.
- Historyczne logi operacji administracyjnych `logs` trafiają do `legacy_audit_logs` z użytkownikiem, opcjonalnym projektem, kontrolerem, akcją i czasem operacji.
- Powiadomienia projektu i logi mailowe są rozdzielone na `project_notifications` oraz `mail_logs`, zgodnie z różnym znaczeniem legacy `notification` i `maillogs`.
- Publiczne komentarze projektu z `comments` zachowują moderację, ukrycie przez autora/admina i relację odpowiedzi po `parentId`.
- Dodatkowe karty weryfikacyjne `detailedverification`, `locationverification` i ich `verificationversion` są importowane z odpowiedziami formularza w JSON i surowym snapshotem wersji.
- Przypisania `coordinatorassignment` i `verifierassignment` są skonsolidowane do `project_user_assignments` z rolą `coordinator` albo `verifier`.
- `taskadvancedverification` jest zachowane w `advanced_verifications` z projektem, jednostką, statusem, datą wysłania i pełnym payloadem legacy.
- Odwołania `taskappealagainstdecision` są importowane do `project_appeals` z treścią, odpowiedzią i decyzją wstępną.
- Rekomendacje `prerecommendations` i `recommendationswjo` są skonsolidowane do `project_department_recommendations` z typem `pre` albo `wjo`.
- Zakresy opiniowania `tasksinitialverification` i `tasksdepartments` są skonsolidowane do `project_department_scopes` z typem zakresu i terminem opinii.
- Monity weryfikacyjne `verificationpressure` są importowane do `verification_pressure_logs` z treścią, odbiorcami, typem monitu i odniesieniem do legacy przydziału departamentu.
- Relacje wielu kategorii projektu są przenoszone przez pivot `category_project` z `taskscategories`.
- `legacy_import_batches` zapisuje `source_path`, statystyki per tabela oraz czas startu i zakończenia.
- Porównanie liczności celowo oznacza RBAC jako `skipped`, bo `authitemchild` jest spłaszczany do uprawnień Spatie i nie ma liniowego odpowiednika 1:1.
- Brakujące relacje są logowane jako `WARN` bez PII i kończą import wyjątkiem domenowym.

## Świadome braki na tym etapie

- Parser `.sql` jest parserem liczności/audytu, nie pełnym importerem rekordów. Import danych nadal powinien iść przez staging MySQL, bo tam MySQL rozwiązuje typy, kodowanie i edge case'y składni dumpa.
- Import nie obejmuje jeszcze rzadkich tabel pomocniczych poza głównym przepływem projektów, weryfikacji i głosowania.

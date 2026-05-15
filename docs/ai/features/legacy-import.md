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

Status: baseline fixture zaimplementowany.

1. Przygotować staging MySQL z profilem `legacy-import`.
2. Dodać komendy Artisan importujące moduły w kolejności zależności.
3. [x] Zapisywać statystyki w `legacy_import_batches`.
4. [x] Dodać fixture z małym wycinkiem dumpa do testów.
5. [x] Porównać liczność, `legacy_id`, statusy i relacje dla baseline modułów.

## Implementacja Laravel

- `LegacyFixtureImportService` importuje podstawowy wycinek danych w transakcji: `taskgroups`, `settings`, `pages`, `tasktypes`, `categories`, `tasks`, `taskscategories`, `taskcosts`, `files`, `filesprivate`, `cocreators`, `taskverification`, `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation`, `detailedverification`, `locationverification`, `verificationversion`, `coordinatorassignment`, `verifierassignment`, `taskdepartmentassignment`, `zkvotes`, `atvotes`, `otvotes`, `atotvotesrejection`, `correspondence`, `taskcomments`, `comments`, `notification`, `maillogs`, `taskcorrection`, `taskchangessuggestion`, `versions`, `newverification`, `votingtokens`, `voters`, `smslogs`, `votecards`, `votes`.
- `LegacyUserImportService` importuje `departments` i `users`.
- Import jest idempotentny po `legacy_id` przez `updateOrCreate`.
- Ustawienia aplikacji zachowują surową wartość z legacy `settings.value`, także jeśli jest to serializowany format Yii/PHP; typizacja ustawień będzie osobnym etapem po potwierdzeniu wszystkich kluczy z dumpa.
- Strony procesu głosowania zachowują `pages.symbol` i HTML z `pages.body` per edycja SBO.
- Powiadomienia projektu i logi mailowe są rozdzielone na `project_notifications` oraz `mail_logs`, zgodnie z różnym znaczeniem legacy `notification` i `maillogs`.
- Publiczne komentarze projektu z `comments` zachowują moderację, ukrycie przez autora/admina i relację odpowiedzi po `parentId`.
- Dodatkowe karty weryfikacyjne `detailedverification`, `locationverification` i ich `verificationversion` są importowane z odpowiedziami formularza w JSON i surowym snapshotem wersji.
- Przypisania `coordinatorassignment` i `verifierassignment` są skonsolidowane do `project_user_assignments` z rolą `coordinator` albo `verifier`.
- Relacje wielu kategorii projektu są przenoszone przez pivot `category_project` z `taskscategories`.
- `legacy_import_batches` zapisuje `source_path`, statystyki per tabela oraz czas startu i zakończenia.
- Brakujące relacje są logowane jako `WARN` bez PII i kończą import wyjątkiem domenowym.

## Świadome braki na tym etapie

- To jeszcze nie jest parser pełnego dumpa MySQL; serwis przyjmuje znormalizowany fixture.
- Brak komendy Artisan do importu z pliku/staging MySQL.
- Import nie obejmuje jeszcze parsera bezpośrednio z dumpa MySQL ani rzadkich tabel pomocniczych poza głównym przepływem projektów, weryfikacji i głosowania.

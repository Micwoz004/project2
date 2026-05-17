# Mapowanie bazy danych

Każda tabela migrowana z legacy powinna zachować `legacy_id`, jeśli reprezentuje rekord źródłowy. Dane PII z dumpa nie są kopiowane do dokumentacji.

## Mapowanie główne

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `taskgroups` | `budget_editions` | Zachowane daty procesu i liczniki kart. |
| `tasktypes` | `project_areas` | `is_local` rozróżnia lokalne i ogólnomiejskie. |
| `tasks` | `projects` | Status jako enum int; część pól legacy zostaje jawnie nazwana snake_case. |
| `taskcosts` | `project_cost_items` | Kwoty jako decimal. |
| `statuses` | `project_status_labels` | Edytowalne nazwy statusów legacy; same wartości statusów pozostają w `ProjectStatus`. |
| `files`, `filesprivate` | `project_files` | Połączone z flagą `is_private` i typem załącznika. |
| `cocreators` | `project_coauthors` | Zgody, dane kontaktowe i adresowe zachowane. |
| `versions` | `project_versions` | Snapshot JSON projektu, plików i kosztów; `status=0` z legacy trafia jako `null`, bo nie jest statusem projektu. |
| `taskcorrection` | `project_corrections` | Lista pól dopuszczonych do poprawy, termin i flaga wykonania korekty. |
| `taskchangessuggestion` | `project_change_suggestions` | Propozycje zmian projektu: stare/nowe dane, koszty i pliki w JSON oraz decyzja autora/admina. |
| `categories`, `taskscategories` | `categories`, `category_project` | Pivot zamiast tabeli legacy o nazwie Yii. |
| `firstnamedictionary`, `lastnamedictionary`, `motherlastnamedictionary` | `dictionary_entries` | Konsolidacja przez `kind`; unikalność `source_table + legacy_id`. |
| `departments` | `departments` | Używane przez projekty, pliki i weryfikacje. |
| `logs` | `legacy_audit_logs` | Historyczny audyt operacji: użytkownik, opcjonalny projekt, kontroler, akcja i treść. |
| `pages` | `content_pages` | Strony publiczne per edycja; `body` zachowuje HTML legacy. |
| `settings` | `application_settings` | Klucz/kategoria/wartość; `value` zachowane surowo, także dla serializacji Yii/PHP. |
| `notification` | `project_notifications` | Powiadomienia związane z projektem: temat, treść, autor, adresat i data wysyłki. |
| `maillogs` | `mail_logs` | Historyczne logi wysyłek mailowych z kontrolerem i akcją legacy. |
| `comments` | `project_public_comments` | Publiczne komentarze projektu z moderacją, ukryciem i relacją rodzic-dziecko. |

## Weryfikacje

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `taskverification` | `formal_verifications` | Odpowiedzi elastycznie w JSON. |
| `taskinitialmeritverification` | `initial_merit_verifications` | Wynik i komentarze zachowane. |
| `taskfinishmeritverification` | `final_merit_verifications` | Wynik końcowy merytoryczny. |
| `taskconsultation` | `consultation_verifications` | Konsultacje departamentów. |
| `taskdepartmentassignment` | `verification_assignments` | Typ przydziału jako enum. |
| `detailedverification` | `detailed_verifications` | Szczegółowa karta weryfikacji; odpowiedzi formularza w JSON, wynik i rekomendacje osobno. |
| `locationverification` | `location_verifications` | Karta lokalizacyjna; odpowiedzi formularza w JSON, wynik i rekomendacje osobno. |
| `verificationversion` | `verification_versions` | Surowy snapshot JSON karty, typ, użytkownik i legacy ID karty. |
| `taskadvancedverification` | `advanced_verifications` | Pełny payload formularza w JSON oraz status, jednostka, operator i data wysłania. |
| `prerecommendations`, `recommendationswjo` | `project_department_recommendations` | Konsolidacja przez `type`; odpowiedzi WJO w JSON. |
| `tasksinitialverification`, `tasksdepartments` | `project_department_scopes` | Jednostki uprawnione do opiniowania; `scope` rozróżnia etap, deadline zachowany dla `tasksdepartments`. |
| `coordinatorassignment`, `verifierassignment` | `project_user_assignments` | Konsolidacja przez `role`: `coordinator` lub `verifier`. |
| `verificationpressure` | `verification_pressure_logs` | Monity ręczne i dyrektorskie z JSON treści/odbiorców oraz legacy ID przydziału departamentu. |

## Głosowanie

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `voters` | `voters` | Dane wyborcy, PESEL, wiek, płeć, IP. |
| `votecards` | `vote_cards` | Status jako enum `VoteCardStatus`. |
| `votes` | `votes` | Jeden rekord projektu i punktów na karcie. |
| `votingtokens` | `voting_tokens` | Typ tokenu, PESEL, telefon, disabled; zgody i adresy legacy w `extra_data`. |
| `newverification` | `voter_registry_hashes` | Hashy nie rozwijamy do PII. |
| `smslogs` | `sms_logs` | Log wysyłek bez treści SMS. |
| `zkvotes`, `atvotes`, `otvotes` | `project_board_votes` | Konsolidacja przez `board_type`. |
| `atotvotesrejection` | `board_vote_rejections` | Powody odrzuceń rad/komisji. |
| `taskappealagainstdecision` | `project_appeals` | Odwołanie autora, odpowiedź komisji, pierwsza decyzja i daty. |

## RBAC i użytkownicy

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `users` | `users` | Rozszerzone o legacy pola i departament. |
| `authitem`, `authitemchild`, `authassignment` | Spatie Permission | Import ról/uprawnień mapuje nazwy legacy. |
| `activations` | `user_activation_tokens` | Typy legacy: aktywacja e-mail, aktywacja SMS, reset hasła; hash zachowany w DB, nie w logach. |
| `pesel` | `legacy_pesel_records` | Administrowany rejestr PESEL legacy; tabela była pusta w dumpie 2025, ale CRUD istnieje. |
| `verification` | `legacy_pesel_verification_entries` | Whitelist autentyczności PESEL używany przez `User::verifyPeselAuthenticity`; tabela pusta w dumpie 2025. |

## Świadome zmiany struktury

- Wiele tabel weryfikacyjnych używa JSON dla odpowiedzi, bo legacy formularze mają liczne pola specyficzne dla etapu. Parytet wymaga testów formularzy przed docelowym usztywnieniem.
- Załączniki publiczne i prywatne są jedną tabelą z flagą `is_private`, zamiast dwóch tabel.
- `projects.attachments_anonymized` odpowiada legacy `tasks.attachmentsAnonimized` jako oświadczenie/bramka publikacji. Legacy nie miało tabeli z zadaniami anonimizacji i nie modyfikowało fizycznie plików w `Task::saveFile()`.
- Głosowania rad/komisji są jedną tabelą `project_board_votes`, żeby uniknąć powielania tych samych pól.
- Korekty projektów mają osobną tabelę `project_corrections`; pola `projects.need_correction`, `correction_no`, `correction_start_time`, `correction_end_time` pozostają szybką denormalizacją dla blokad edycji i kompatybilności z legacy `tasks`.
- Dane autora składane w publicznym formularzu są utrzymywane jako snapshot JSON w `projects.authors`; `projects.creator_id` pozostaje relacją do `users`, gdy formularz jest składany w aktywnej sesji. To pozwala zachować wartości z formularza 1:1 bez wymuszania tworzenia konta użytkownika w baseline logiki.
- Trzy słowniki imion/nazwisk są połączone w `dictionary_entries`, dlatego `legacy_id` jest unikalne tylko razem z `source_table`.
- Anonimizacja użytkownika maskuje `users.email` unikalnym adresem technicznym zamiast legacy `*`, bo Laravel auth wymaga unikalności tej kolumny.
- `tasks.categoryId = NULL/0` i `tasks.taskTypeId = 0` są odwzorowane jako nullable relacje (`category_id`, `project_area_id`), bo dump zawiera historyczne projekty bez prawidłowego słownika.
- Osierocone rekordy `taskfinishmeritverification`, `otvotes`, `atvotes` i `authassignment` bez istniejącego projektu/użytkownika nie mają bezpiecznego odpowiednika relacyjnego. Importer pomija je z `WARN`, a audyt liczności filtruje źródło do rekordów możliwych do odwzorowania.
- `atotvotesrejection` mapuje legacy `votesType` na `board_type` i `createdBy` na `created_by_id`.
- `result_tie_decisions` nie ma odpowiednika legacy. Tabela zapisuje ręczną decyzję remisu, ponieważ w legacy nie znaleziono automatycznej reguły rozstrzygania zwycięzcy przy identycznej liczbie punktów.
- `legacy_id` jest unikalne i opcjonalne, co umożliwia import etapami.

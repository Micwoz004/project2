# Mapowanie bazy danych

Każda tabela migrowana z legacy powinna zachować `legacy_id`, jeśli reprezentuje rekord źródłowy. Dane PII z dumpa nie są kopiowane do dokumentacji.

## Mapowanie główne

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `taskgroups` | `budget_editions` | Zachowane daty procesu i liczniki kart. |
| `tasktypes` | `project_areas` | `is_local` rozróżnia lokalne i ogólnomiejskie. |
| `tasks` | `projects` | Status jako enum int; część pól legacy zostaje jawnie nazwana snake_case. |
| `taskcosts` | `project_cost_items` | Kwoty jako decimal. |
| `files`, `filesprivate` | `project_files` | Połączone z flagą `is_private` i typem załącznika. |
| `cocreators` | `project_coauthors` | Zgody i dane kontaktowe zachowane. |
| `versions` | `project_versions` | Snapshot JSON projektu, plików i kosztów; `status=0` z legacy trafia jako `null`, bo nie jest statusem projektu. |
| `taskcorrection` | `project_corrections` | Lista pól dopuszczonych do poprawy, termin i flaga wykonania korekty. |
| `categories`, `taskscategories` | `categories`, `category_project` | Pivot zamiast tabeli legacy o nazwie Yii. |
| `firstnamedictionary`, `lastnamedictionary`, `motherlastnamedictionary` | `dictionary_entries` | Konsolidacja przez `kind`; unikalność `source_table + legacy_id`. |
| `departments` | `departments` | Używane przez projekty, pliki i weryfikacje. |
| `pages` | `content_pages` | Strony publiczne per edycja. |
| `settings` | `application_settings` | Klucz/kategoria/wartość. |

## Weryfikacje

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `taskverification` | `formal_verifications` | Odpowiedzi elastycznie w JSON. |
| `taskinitialmeritverification` | `initial_merit_verifications` | Wynik i komentarze zachowane. |
| `taskfinishmeritverification` | `final_merit_verifications` | Wynik końcowy merytoryczny. |
| `taskconsultation` | `consultation_verifications` | Konsultacje departamentów. |
| `taskdepartmentassignment` | `verification_assignments` | Typ przydziału jako enum. |
| `detailedverification`, `locationverification`, `verificationversion` | pola JSON / kolejne migracje | Do doprecyzowania przy pełnym module weryfikacji. |

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

## RBAC i użytkownicy

| Legacy | Laravel/PostgreSQL | Uwagi |
| --- | --- | --- |
| `users` | `users` | Rozszerzone o legacy pola i departament. |
| `authitem`, `authitemchild`, `authassignment` | Spatie Permission | Import ról/uprawnień mapuje nazwy legacy. |

## Świadome zmiany struktury

- Wiele tabel weryfikacyjnych używa JSON dla odpowiedzi, bo legacy formularze mają liczne pola specyficzne dla etapu. Parytet wymaga testów formularzy przed docelowym usztywnieniem.
- Załączniki publiczne i prywatne są jedną tabelą z flagą `is_private`, zamiast dwóch tabel.
- Głosowania rad/komisji są jedną tabelą `project_board_votes`, żeby uniknąć powielania tych samych pól.
- Korekty projektów mają osobną tabelę `project_corrections`; pola `projects.need_correction`, `correction_no`, `correction_start_time`, `correction_end_time` pozostają szybką denormalizacją dla blokad edycji i kompatybilności z legacy `tasks`.
- Trzy słowniki imion/nazwisk są połączone w `dictionary_entries`, dlatego `legacy_id` jest unikalne tylko razem z `source_table`.
- `legacy_id` jest unikalne i opcjonalne, co umożliwia import etapami.

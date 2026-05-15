# Powiadomienia, korespondencja i komentarze

1. Legacy: `Notification`, `VerificationNotification`, `Correspondence`, `Comments`, `TaskComments`, kontrolery korespondencji.
2. Tabele: `notification`, `correspondence`, `comments`, `taskcomments`, `maillogs`.
3. Dane wejściowe: treść wiadomości, adresat, projekt, status odczytu.
4. Dane zapisywane: wiadomości, komentarze, logi maili.
5. Statusy: odczytane/nieodczytane; status projektu może wymuszać powiadomienia.
6. Walidacje: uprawnienie do projektu, brak pustej treści, ochrona PII w logach.
7. Role: autor, BDO, weryfikatorzy, administracja.
8. Edge case: wiadomość do wielu odbiorców, ponowna wysyłka, historyczny brak użytkownika.
9. Laravel: `CorrespondenceMessage`, `ProjectComment`, akcje domenowe komunikacji, kolejki mailowe.
10. Zgodność: testy uprawnień i odczytu, porównanie typów powiadomień i momentów wysyłki z legacy.

## Plan wdrożenia

Status: baseline domenowy rozpoczęty.

1. Spisać wszystkie punkty wysyłki maili i powiadomień z legacy.
2. Dodać kolejki i szablony wiadomości.
3. [x] Dodać korespondencję projektu z widocznością per rola.
4. [x] Dodać komentarze wewnętrzne.
5. [x] Pokryć testami uprawnienia i odczyt.
6. [x] Dodać import fixture dla `correspondence` i `taskcomments`.
7. [x] Dodać import fixture dla `notification` i `maillogs`.

## Implementacja Laravel

- `AddProjectCommentAction` dodaje wewnętrzny komentarz projektu tylko dla `projects.manage`, `projects.verify`, `admin` lub `bdo`.
- `SendProjectCorrespondenceMessageAction` zapisuje wiadomość dla autora projektu albo użytkownika z uprawnieniami administracyjnymi/weryfikacyjnymi.
- `MarkCorrespondenceMessageReadAction` oznacza wiadomość jako przeczytaną przez adresata albo uprawnionego operatora.
- `LegacyFixtureImportService` przenosi historyczną korespondencję i komentarze projektu po `legacy_id`, wiążąc je z projektem oraz opcjonalnym użytkownikiem legacy.
- `ProjectNotification` przenosi legacy `notification`: projekt, twórcę, adresata, email autora, temat, treść i datę wysyłki.
- `MailLog` przenosi legacy `maillogs`: adres email, temat, treść, kontroler, akcję, operatora i czas wysyłki.
- Logi zapisują identyfikatory projektu/użytkownika/wiadomości, ale nie zapisują treści wiadomości ani komentarzy.

## Świadome braki na tym etapie

- Brak kolejek i realnych wysyłek mail/SMS.
- Brak pełnej mapy punktów wywołania historycznych szablonów wiadomości.
- Brak komentarzy publicznych, jeśli legacy rozróżniało ich widoczność od komentarzy wewnętrznych.

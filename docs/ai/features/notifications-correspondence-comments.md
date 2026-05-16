# Powiadomienia, korespondencja i komentarze

1. Legacy: `Notification`, `VerificationNotification`, `Correspondence`, `Comments`, `TaskComments`, kontrolery korespondencji.
2. Tabele: `notification`, `correspondence`, `comments`, `taskcomments`, `maillogs`, `verificationpressure`.
3. Dane wejściowe: treść wiadomości, adresat, projekt, status odczytu.
4. Dane zapisywane: wiadomości, komentarze, logi maili.
5. Statusy: odczytane/nieodczytane; status projektu może wymuszać powiadomienia.
6. Walidacje: uprawnienie do projektu, brak pustej treści, ochrona PII w logach.
7. Role: autor, BDO, weryfikatorzy, administracja.
8. Edge case: wiadomość do wielu odbiorców, ponowna wysyłka, historyczny brak użytkownika.
9. Laravel: `CorrespondenceMessage`, `ProjectComment`, akcje domenowe komunikacji, kolejki mailowe.
10. Zgodność: testy uprawnień i odczytu, porównanie typów powiadomień i momentów wysyłki z legacy.

## Plan wdrożenia

Status: baseline domenowy, import fixture i pierwsza kolejka mailowa wdrożone.

1. [x] Spisać bazową mapę tabel i punktów komunikacji z legacy.
2. [x] Dodać bazowe kolejki i szablony wiadomości projektu.
3. [x] Dodać korespondencję projektu z widocznością per rola.
4. [x] Dodać komentarze wewnętrzne.
5. [x] Pokryć testami uprawnienia i odczyt.
6. [x] Dodać import fixture dla `correspondence` i `taskcomments`.
7. [x] Dodać import fixture dla `notification` i `maillogs`.
8. [x] Dodać import fixture dla publicznych `comments` z moderacją i relacją `parentId`.
9. [x] Dodać import fixture dla monitów weryfikacyjnych `verificationpressure`.
10. [ ] Uzupełnić pełną mapę wszystkich punktów wysyłki mail/SMS względem kontrolerów legacy.

## Implementacja Laravel

- `AddProjectCommentAction` dodaje wewnętrzny komentarz projektu tylko dla `projects.manage`, `projects.verify`, `admin` lub `bdo`.
- `SendProjectCorrespondenceMessageAction` zapisuje wiadomość dla autora projektu albo użytkownika z uprawnieniami administracyjnymi/weryfikacyjnymi.
- `MarkCorrespondenceMessageReadAction` oznacza wiadomość jako przeczytaną przez adresata albo uprawnionego operatora.
- `LegacyFixtureImportService` przenosi historyczną korespondencję i komentarze projektu po `legacy_id`, wiążąc je z projektem oraz opcjonalnym użytkownikiem legacy.
- `ProjectNotification` przenosi legacy `notification`: projekt, twórcę, adresata, email autora, temat, treść i datę wysyłki.
- `MailLog` przenosi legacy `maillogs`: adres email, temat, treść, kontroler, akcję, operatora i czas wysyłki.
- `ProjectPublicComment` przenosi legacy `comments`: komentarze publiczne przy projekcie, autora, rodzica, flagi `hidden`, `adminHidden`, `moderated` i czas utworzenia.
- `VerificationPressureLog` przenosi legacy monity weryfikacyjne bez wysyłki wiadomości: treść JSON, listę odbiorców JSON, typ monitu i datę wysłania.
- `ProjectNotificationTemplate` definiuje bazowe szablony dla korespondencji, korekty formalnej, monitu weryfikacyjnego i zmiany statusu projektu.
- `QueueProjectNotificationAction` tworzy `ProjectNotification`, waliduje adres odbiorcy na granicy operacji i dispatchuje `SendProjectNotificationJob`.
- `SendProjectNotificationJob` wysyła wiadomość przez Laravel Mail i zapisuje ślad w `MailLog`, zachowując audyt legacy `maillogs`.
- `SendProjectCorrespondenceMessageAction` po zapisie korespondencji kolejkuje powiadomienie mailowe do adresata.
- Logi zapisują identyfikatory projektu/użytkownika/wiadomości, ale nie zapisują treści wiadomości ani komentarzy.

## Świadome braki na tym etapie

- Kolejka obejmuje bazowe powiadomienia projektu; pełna mapa wszystkich punktów wywołania historycznych szablonów mail/SMS nadal wymaga uzupełnienia z kontrolerów legacy.
- Brak integracji z operatorem SMS dla powiadomień innych niż token głosowania.
- Brak UI i akcji domenowych dla publicznego dodawania, ukrywania i moderowania komentarzy.

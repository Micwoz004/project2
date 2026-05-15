# Powiadomienia, korespondencja i komentarze

1. Legacy: `Notification`, `VerificationNotification`, `Correspondence`, `Comments`, `TaskComments`, kontrolery korespondencji.
2. Tabele: `notification`, `correspondence`, `comments`, `taskcomments`, `maillogs`.
3. Dane wejściowe: treść wiadomości, adresat, projekt, status odczytu.
4. Dane zapisywane: wiadomości, komentarze, logi maili.
5. Statusy: odczytane/nieodczytane; status projektu może wymuszać powiadomienia.
6. Walidacje: uprawnienie do projektu, brak pustej treści, ochrona PII w logach.
7. Role: autor, BDO, weryfikatorzy, administracja.
8. Edge case: wiadomość do wielu odbiorców, ponowna wysyłka, historyczny brak użytkownika.
9. Laravel: `CorrespondenceMessage`, `ProjectComment`, kolejki mailowe.
10. Zgodność: porównać typy powiadomień i momenty wysyłki z legacy.

## Plan wdrożenia

Status: zaplanowane w etapie 3.

1. Spisać wszystkie punkty wysyłki maili i powiadomień z legacy.
2. Dodać kolejki i szablony wiadomości.
3. Dodać korespondencję projektu z widocznością per rola.
4. Dodać komentarze wewnętrzne i publiczne.
5. Pokryć testami uprawnienia, odczyt i brak PII w logach.

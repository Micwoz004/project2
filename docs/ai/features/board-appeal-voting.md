# Głosowania rad i odwołania

1. Legacy: `ZkVote`, `AtVote`, `OtVote`, `AtOtVotesRejection`, `TaskAppealAgainstDecision`, kontrolery weryfikacji.
2. Tabele: `zkvotes`, `atvotes`, `otvotes`, `atotvotesrejection`, `taskappealagainstdecision`, `tasks`.
3. Dane wejściowe: wybór członka rady/komisji, głos, komentarz, odrzucenie, treść odwołania.
4. Dane zapisywane: głos członka, typ rady/komisji, powód odrzucenia, odwołanie i odpowiedź komisji.
5. Statusy: `DuringTeamVerification=15`, `TeamAccepted=16`, `TeamRejected=17`, `TeamForReverification=19`, odwołania `20..22`, negatywne `-13`, `-14`.
6. Walidacje: jeden głos użytkownika na projekt i typ, wymagany komentarz przy odrzuceniu.
7. Role: członkowie Rady SBO, komisje odwoławcze, administratorzy.
8. Edge case: remis, brak quorum, cofnięcie głosu, odwołanie po terminie.
9. Laravel: `project_board_votes`, `board_vote_rejections`, enum typu rady/komisji.
10. Zgodność: porównać wynik decyzji z legacy dla tych samych głosów.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 4.

1. [x] Zdefiniować enum typu rady/komisji i wartości głosu.
2. [x] Dodać akcję oddania głosu członka.
3. [x] Dodać reguły jednego głosu per projekt/użytkownik/typ.
4. [x] Dodać kalkulator decyzji i odwołań.
5. [x] Pokryć testami remisy, odrzucenia i ponowną weryfikację.
6. [x] Dodać import fixture dla `zkvotes`, `atvotes`, `otvotes` i `atotvotesrejection`.
7. [x] Dodać import fixture dla `taskappealagainstdecision`.
8. [x] Dodać szczególny głos przewodniczącego ZK dla wyniku granicznego 4:4.
9. [x] Dodać restart i zamknięcie głosowania OT/AT zgodne z akcjami `forceClose`/`forceRestart`.
10. [x] Dodać bramki uprawnień dla ról rad/komisji i zarządzania głosowaniem.
11. [x] Podpiąć w Filament akcje zamknięcia i restartu głosowań OT/AT z bramką `manage-board-voting`.
12. [x] Uzupełnić UI/Filament dla oddawania głosów członków rad/komisji.

## Rozpoznane reguły legacy

- `ZkVote` zapisuje `vote=1` albo `vote=-1`.
- `Task::zkAccepted()` uznaje projekt, gdy głosów pozytywnych jest więcej niż wymagane minimum albo przy wyniku granicznym rozstrzyga głos przewodniczącego ZK.
- `ProcessingController::actionProcessZKVote()` w prostym przeliczeniu ustawia `Picked`, gdy pozytywnych głosów jest więcej, `RejectedZo`, gdy negatywnych jest więcej, a remis zostawia bez rozstrzygnięcia.
- `OtVote` ma wybory: `1=wstrzymuje się`, `2=do ponownej weryfikacji`, `3=odrzucony z możliwością odwołania`, `4=zatwierdzony na listę`.
- `ProcessingController::actionProcessOTVote()` ustawia `Picked`, gdy akceptacja ma samodzielną większość; przy remisie odrzucenie/ponowna weryfikacja nie rozstrzyga; odrzucenie ustawia `TeamRejected`, ponowna weryfikacja ustawia `TeamForReverification`.
- `AtVote` ma wybory: `1=wstrzymuje się`, `2=zatwierdzony na listę`, `3=odrzucony ostatecznie`.
- `ProcessingController::actionProcessATVote()` przy remisie nie rozstrzyga; przewaga odrzucenia ustawia `TeamRejectedFinally`, przewaga akceptacji `Picked`.
- `AtOtVotesRejection` wymaga `taskId`, `votesType` i `comment`; dotyczy typów `AT` i `OT`.
- `TaskAppealAgainstDecision` wymaga `appealMessage` w scenariuszu składania odwołania, `responseToAppeal` w scenariuszu odpowiedzi, pilnuje limitu 5000 znaków treści i maksymalnie 5 załączników typu prywatnego.
- `firstDecision=1` oznacza wstępną akceptację rozpatrzenia odwołania, a `firstDecision=2` odrzucenie na etapie wstępnym.

## Zaimplementowany odpowiednik Laravel

- `BoardType` rozróżnia `ZK`, `OT`, `AT`.
- Osobne enumy wyborów zachowują różne znaczenia tych samych liczb w legacy: `ZkVoteChoice`, `OtVoteChoice`, `AtVoteChoice`.
- `ProjectBoardVote` konsoliduje `zkvotes`, `otvotes`, `atvotes`.
- `BoardVoteRejection` konsoliduje `atotvotesrejection`.
- `CastProjectBoardVoteAction` waliduje wybór zależnie od typu głosowania i blokuje duplikat per projekt/użytkownik/typ.
- `RecordBoardVoteRejectionAction` wymaga komentarza i dopuszcza uzasadnienia tylko dla `AT`/`OT`.
- `StartBoardVotingAction` ustawia status projektu na `DuringTeamVerification` albo `DuringTeamRecallVerification` i zachowuje flagę historycznego odrzucenia.
- `CloseBoardVotingAction` zamyka głosowanie OT jako `TeamClosedVerification` oraz AT jako `TeamRecallClosedVerification`, bez usuwania głosów.
- `RestartBoardVotingAction` usuwa głosy OT albo AT danego projektu i przywraca status aktywnego głosowania, tak jak legacy `actionForceRestartOTVoting()` i `actionForceRestartATVoting()`.
- Gate `cast-board-vote` dopuszcza role `president/vicepresident/verifier ZK` do ZK oraz `president/vicepresident/verifier ZOD` do OT/AT; `manage-board-voting` jest dla ról z `projects.manage` oraz admin/BDO.
- `ProjectResource` w Filament pokazuje akcje zamknięcia/restartu OT albo AT tylko dla projektów w pasujących statusach i tylko użytkownikom przechodzącym `manage-board-voting`.
- `ProjectResource` pokazuje akcje oddania głosu ZK/OT/AT tylko członkom właściwych ról, w statusach aktywnego głosowania i tylko do momentu oddania własnego głosu.
- `BoardDecisionResolver` liczy decyzje zgodnie z akcjami `actionProcessZKVote`, `actionProcessOTVote`, `actionProcessATVote`.
- Dla ZK resolver zachowuje szczególną regułę `Task::zkAccepted()`: przy wyniku 4:4 głos użytkownika z rolą `president ZK` rozstrzyga akceptację albo odrzucenie.
- `LegacyFixtureImportService` konsoliduje historyczne głosy rad/komisji w `project_board_votes` i uzasadnienia odrzuceń w `board_vote_rejections`.
- `ProjectAppeal` odwzorowuje `taskappealagainstdecision`: treść odwołania, odpowiedź komisji, daty oraz pierwszą decyzję.

## Zgodność do sprawdzenia

- Porównać etykiety wyborów w modalach Filament z finalnymi tekstami legacy, jeśli miasto będzie wymagało identycznego brzmienia komunikatów.

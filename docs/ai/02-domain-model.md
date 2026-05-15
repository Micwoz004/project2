# Model domenowy Laravel

## Moduły

- `BudgetEditions` - edycje SBO, harmonogram, stan procesu.
- `Projects` - projekty, statusy, kosztorysy, współautorzy, wersje.
- `Files` - załączniki publiczne/prywatne.
- `Verification` - weryfikacja formalna, wstępna, merytoryczna, konsultacje i przydziały.
- `Voting` - wyborcy, tokeny, karty głosowania, głosy, hashe uprawnionych, SMS.
- `Results` - liczenie wyników z ważnych kart.
- `Reports` - eksporty i raporty publiczne/administracyjne.
- `LegacyImport` - kontrolowany import fixture i przyszły import dumpa ze statystykami.
- `Users` - użytkownicy, departamenty, role/uprawnienia.
- `Settings` - ustawienia, strony treści i słowniki.
- `Dictionaries` - skonsolidowane słowniki imion, nazwisk i nazwisk matek z legacy.
- `Communications` - korespondencja i komentarze.

## Najważniejsze encje

- `BudgetEdition` odwzorowuje `taskgroups`.
- `ProjectArea` odwzorowuje `tasktypes`.
- `Project` odwzorowuje `tasks`.
- `ProjectCostItem` odwzorowuje `taskcosts`.
- `ProjectFile` odwzorowuje `files` i `filesprivate`.
- `ProjectCoauthor` odwzorowuje `cocreators`.
- `ProjectVersion` odwzorowuje `versions`.
- `ProjectCorrection` odwzorowuje `taskcorrection`.
- `ProjectChangeSuggestion` odwzorowuje `taskchangessuggestion`.
- `VoteCard`, `Vote`, `Voter`, `VotingToken`, `VoterRegistryHash`, `SmsLog` odwzorowują głosowanie.
- `Department` odwzorowuje `departments`.
- `ApplicationSetting` odwzorowuje `settings` z zachowaniem surowych wartości legacy.
- `ContentPage` odwzorowuje `pages` per edycja SBO i symbol strony procesu.
- `CorrespondenceMessage`, `ProjectComment`, `ProjectPublicComment`, `ProjectNotification`, `MailLog` odwzorowują komunikację, komentarze i historię wysyłek.

## Statusy projektu

Statusy legacy są zachowane jako `ProjectStatus` z wartościami integer: `1,2,3,4,5,10..25,-1,-2,-3,-4,-10,-11,-12,-13,-14`. Enum ma osobne etykiety publiczne i administracyjne.

## Reguły umieszczone w domenie

- `SubmitProjectAction` - składanie projektu, lista poparcia, kosztorys, blokada statusu, zakaz URL, wersjonowanie.
- `StartCorrectionAction` i `ApplyCorrectionAction` - okno korekty, whitelist pól z `taskcorrection`, blokada po terminie i snapshot wersji.
- `DecideProjectChangeSuggestionAction` - akceptacja/odrzucenie propozycji zmian z `taskchangessuggestion`.
- `BeginFormalVerificationAction` i `CompleteFormalVerificationAction` - bazowe przejścia oceny formalnej, wymóg listy poparcia przy wyniku pozytywnym i uzasadnienie przy negatywnym.
- `AssignVerificationDepartmentAction` - przydział departamentu do typu weryfikacji.
- `SubmitInitialMeritVerificationAction`, `SubmitFinalMeritVerificationAction`, `SubmitConsultationVerificationAction` - statusy kart, oznaczenie przydziału jako wysłanego, wynik wstępny/końcowy i walidacja kosztów.
- `CastProjectBoardVoteAction`, `RecordBoardVoteRejectionAction`, `StartBoardVotingAction`, `BoardDecisionResolver` - głosy ZK/OT/AT, unikalność głosu, uzasadnienia odrzucenia i przejścia statusów.
- `BudgetEditionStateResolver` - stan procesu zgodny z `TaskGroup::getState`.
- `PeselService` - checksum, data urodzenia, wiek, płeć.
- `VoterHashService` - hash `newverification` zgodny z legacy salt `D0FB5FC74E`.
- `VotingTokenService` - 6-cyfrowy SMS token, limit 5 kodów na telefon, aktywacja po telefonie i kodzie, unieważnianie starych oraz zużytych tokenów PESEL.
- `CastVoteService` - okno głosowania, checksum i unikalność PESEL, hash rejestru `newverification`, oświadczenia, zgoda rodzica, brak PESEL jako `Verifying`, limity lokalne/ogólnomiejskie, tylko `STATUS_PICKED`, transakcja zapisu.
- `RegisterPaperVoteCardAction` - ręczna rejestracja papierowej karty z numeracją, operatorem i wspólną walidacją głosu.
- `UpdateVoteCardStatusAction` - administracyjna zmiana statusu karty z operatorem, czasem obsługi i notatką.
- `ResultsCalculator` - sumowanie punktów wyłącznie z kart `VoteCardStatus::Accepted`, deterministyczny ranking projektów i agregacje po obszarach/kategoriach.
- `ResultsPublicationService` - publiczna widoczność wyników wyłącznie w etapie ogłaszania wyników.
- `VoteCardReportService` - zagregowane statusy kart, demografia oraz raporty wieku, płci i typu karty per projekt z zaakceptowanych kart bez PII.
- `PublicResultsCsvExporter` - publiczny eksport wyników bez danych wyborców.
- `LegacyFixtureImportService` - transakcyjny, idempotentny import baseline danych legacy po `legacy_id`.
- `LegacyDictionaryImportService` - import słowników legacy po `source_table + legacy_id`.
- `LegacyUserImportService` - import departamentów i użytkowników legacy po `legacy_id`.
- `LegacyRbacImportService` - import ról, operacji, relacji i przypisań RBAC z Yii do Spatie.
- `AddProjectCommentAction`, `SendProjectCorrespondenceMessageAction`, `MarkCorrespondenceMessageReadAction` - bazowa komunikacja projektu bez logowania treści.

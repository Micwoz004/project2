# Analiza legacy

## Technologia

- Framework: Yii 1.x.
- Język: PHP legacy.
- Baza: MySQL.
- RBAC: tabele Yii `authitem`, `authitemchild`, `authassignment`.
- Frontend: widoki Yii w `web/protected/views`.
- Raporty/eksporty: kontrolery Yii, widoki i katalog `raporty_sbo`.

## Struktura katalogów

- `web/protected/controllers` - kontrolery administracyjne, publiczne, głosowanie, raporty i importery.
- `web/protected/models` - modele ActiveRecord i formularze z walidacją.
- `web/protected/views` - formularze, listy projektów, widoki głosowania i administracji.
- `web/protected/components` - komponenty i zachowania Yii.
- `db/*.sql` - pomocnicze skrypty SQL.
- `sbo2025_prod.sql` - dump produkcyjny, źródło schematu i danych.

## Główne kontrolery

- `TaskController`, `TaskProposeController` - projekty, zgłaszanie, edycja, statusy, widoki administracyjne.
- `TaskGroupController` - edycje SBO i harmonogram procesu.
- `VotingController`, `VotingTokenController`, `VoteCardController`, `VoteController`, `VoterController` - proces głosowania, tokeny, karty, głosy.
- `MeritVerificationController`, `VerificationVersionController`, `VerificationPressureController` - weryfikacje.
- `ResultsController`, `PublicReportController`, `ReportController` - wyniki, raporty publiczne i administracyjne.
- `UserController`, `CategoryController`, `SettingsController`, `PageController` - użytkownicy, słowniki, ustawienia, treści.
- `CorrespondenceController`, `CommentsController`, `TaskCommentsController` - komunikacja i komentarze.
- `ImporterController`, `PeselController`, `ProcessingController` - importy i przetwarzanie pomocnicze.

## Główne modele i formularze

- Projekty: `Task`, `TaskCost`, `TaskType`, `Category`, `Cocreator`, `File`, `FilePrivate`, `Version`.
- Edycje: `TaskGroup`, `Page`, `SettingsForm`.
- Weryfikacje: `TaskVerification`, `TaskInitialMeritVerification`, `TaskFinishMeritVerification`, `TaskConsultation`, `TaskDepartmentAssignment`, `DetailedVerification`, `LocationVerification`.
- Głosowanie: `VotingForm`, `VotingTokenForm`, `VoteCardForm`, `VoteCard`, `Vote`, `Voter`, `VotingToken`, `Smslogs`.
- Rady/komisje: `ZkVote`, `AtVote`, `OtVote`, `AtOtVotesRejection`.
- RBAC/użytkownicy: `User`, tabele `authitem*`.

## Tabele z dumpa

Rozpoznane tabele: `activations`, `atotvotesrejection`, `atvotes`, `authassignment`, `authitem`, `authitemchild`, `categories`, `cocreators`, `comments`, `coordinatorassignment`, `correspondence`, `departments`, `detailedverification`, `files`, `filesprivate`, `firstnamedictionary`, `lastnamedictionary`, `locationverification`, `logs`, `maillogs`, `motherlastnamedictionary`, `newverification`, `notification`, `otvotes`, `pages`, `pesel`, `prerecommendations`, `recommendationswjo`, `settings`, `smslogs`, `statuses`, `taskadvancedverification`, `taskappealagainstdecision`, `taskchangessuggestion`, `taskcomments`, `taskconsultation`, `taskcorrection`, `taskcosts`, `taskdepartmentassignment`, `taskfinishmeritverification`, `taskgroups`, `taskinitialmeritverification`, `tasks`, `taskscategories`, `tasksdepartments`, `tasksinitialverification`, `tasktypes`, `taskverification`, `users`, `verification`, `verificationpressure`, `verificationversion`, `verifierassignment`, `versions`, `votecards`, `voters`, `votes`, `votingtokens`, `yiicache`, `yiisession`, `zkvotes`.

## Miejsca krytycznej logiki

- `Task::rules` - wymagane pola projektu, walidacja URL, limity tekstów, załączniki, kosztorys, współautorzy.
- `TaskGroup::getState` - stan procesu SBO liczony z dat edycji.
- `VotingForm` - limity głosów, listy projektów, podsumowanie, wybór projektów.
- `VotingTokenForm` - PESEL, hash osoby, limit SMS, zgoda rodzica, oświadczenia mieszkańca/ucznia/studenta/pracownika.
- `VoteCard` i `VoteCardForm` - statusy kart, walidacja kart papierowych/elektronicznych.
- `ResultsController` i `PublicReportController` - liczenie wyników oraz widoczność raportów.
- Modele weryfikacji `TaskVerification*` - statusy i odpowiedzi formalne/merytoryczne.

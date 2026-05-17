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
10. [x] Uzupełnić pełną mapę wszystkich punktów wysyłki mail/SMS względem kontrolerów legacy.
11. [x] Dodać wysyłkę i obsługę potwierdzenia współautora z triggera `cocreator.confirmation`.
12. [x] Dodać publiczną wiadomość do autora projektu z triggera `project.contact_message`.

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
- `LegacyCommunicationTrigger` jest kodową mapą punktów wysyłki z Yii: wskazuje źródło legacy, kanał `mail`/`sms`, odbiorcę, klucze `settings` i ewentualny odpowiednik `ProjectNotificationTemplate`.
- `QueueProjectNotificationAction` tworzy `ProjectNotification`, waliduje adres odbiorcy na granicy operacji i dispatchuje `SendProjectNotificationJob`.
- `SendProjectNotificationJob` wysyła wiadomość przez Laravel Mail i zapisuje ślad w `MailLog`, zachowując audyt legacy `maillogs`.
- `SendProjectCorrespondenceMessageAction` po zapisie korespondencji kolejkuje powiadomienie mailowe do adresata.
- `SendProjectCoauthorConfirmationAction` obsługuje trigger `cocreator.confirmation`: generuje brakujący hash współautora, zapisuje notyfikację, wysyła mail przez kolejkę i używa kompatybilnego linku `/activation/confirmCocreator`.
- `SendProjectContactMessageAction` obsługuje trigger `project.contact_message`: waliduje `topic`, `email`, `content` jak legacy `ContactForm`, wymaga adresu e-mail autora projektu i zapisuje wiadomość z legacy prefiksem `Otrzymałeś/aś wiadomość od ...`.
- `VoteSummaryNotificationService` obsługuje trigger `voting.summary.sms` oraz ścieżkę błędu `voting.summary.sms_failure_email`; wysyłka podsumowania nie wpływa na ważność zapisanego głosu.
- Logi zapisują identyfikatory projektu/użytkownika/wiadomości, ale nie zapisują treści wiadomości ani komentarzy.

## Mapa punktów wysyłki legacy

| Trigger Laravel | Legacy | Kanał | Klucze ustawień / źródło treści |
|---|---|---|---|
| `task.correspondence` | `TaskController::actionView` | mail | `emailTitleTaskCorrespondence`, `emailBodyTaskCorrespondence` |
| `task.submitted` | `TaskController::actionUpdate` | mail | `emailTitleTaskProposeSubmit`, `emailBodyTaskProposeSubmit` |
| `task.edited_paper` | `TaskController::actionUpdatePaper` | mail | `emailTitleTaskEdited`, `emailBodyTaskEdited` |
| `task.assigned_verifier` | `TaskController::actionAssignVerifier` | mail | `emailTitleTaskAssigned`, `emailBodyTaskAssigned` |
| `task.assigned_coordinator` | `TaskController::actionAssignCoordinator` | mail | `emailTitleTaskAssigned`, `emailBodyTaskAssigned` |
| `task.back_to_working_copy.email` | `TaskController::actionCallToCorrection` | mail | `emailTitleTaskBackToWorkingCopy`, `emailBodyTaskBackToWorkingCopy` |
| `task.call_to_correction.sms` | `TaskController::actionCallToCorrection` | sms | `smsCallToCorrection` |
| `task.status.rejected_formal` | `Task::sendStatusNotification` | mail | `emailTitleVerificationNegativeFormal`, `emailBodyVerificationNegativeFormal` |
| `task.status.rejected_wjo` | `Task::sendStatusNotification` | mail | `emailTitleVerificationNegativeImpossible`, `emailBodyVerificationNegativeImpossible` |
| `task.status.recommended_wjo` | `Task::sendStatusNotification` | mail | `emailTitleVerificationContinued`, `emailBodyVerificationContinued` |
| `task.status.picked` | `Task::sendStatusNotification` | mail | `emailTitleVerificationPositive`, `emailBodyVerificationPositive` |
| `verification.formal.positive` | `ProcessingController::actionFormalVerification` | mail | `emailTitleTaskFormallyVerifiedPositive`, `emailBodyTaskFormallyVerifiedPositive` |
| `verification.formal.negative` | `ProcessingController::actionFormalVerification` | mail | `emailTitleTaskFormallyVerifiedNegative`, `emailBodyTaskFormallyVerifiedNegative` |
| `verification.wjo.department_assigned` | `ProcessingController::actionWjoDecree` / `actionAddAnotherDepartment` | mail | `emailTitleToWjoDepartments`, `emailBodyToWjoDepartments` |
| `verification.zk.recommended_positive` | `ProcessingController::actionSetZkResult` | mail | `emailTitleTaskRecommendedZo`, `emailBodyTaskRecommendedZo` |
| `verification.zk.recommended_negative` | `ProcessingController::actionSetZkResult` | mail | `emailTitleTaskRecommendedZoNegative`, `emailBodyTaskRecommendedZoNegative` |
| `verification.zk.manual_accepted` | `ProcessingController::actionAcceptTask` | mail | treść hardcoded legacy |
| `verification.zk.manual_rejected` | `ProcessingController::actionRejectTask` | mail | treść hardcoded legacy |
| `verification.published.author` | `ProcessingController::sendPublishedNotificationToAuthor` | mail | `emailTitleTaskVerificationPublished`, `emailBodyTaskVerificationPublished` |
| `verification.published.verifier` | `ProcessingController::sendPublishedNotificationToVerifiers` | mail | `emailTitleVerifierVerificationPublished`, `emailBodyVerifierVerificationPublished` |
| `verification.document.with_attachment` | `DocumentController::sendNotificationEmailWithAttachment` | mail | `notification.notificationSubject`, `notification.notificationText`, PDF karty |
| `verification.pressure.automatic` | `VerificationPressure::beforeValidate` / `send` | mail | `emailTitleTaskVerificationPressureType`, `emailBodyTaskVerificationPressureType` |
| `verification.pressure.manual` | `VerificationPressureForm::sendManualPressure` | mail | treść ręczna operatora |
| `public_comment.added` | `CommentsController::actionAddComment` | mail | `emailTitleCommentAdded`, `emailBodyCommentAdded` |
| `public_comment.admin_hidden` | `CommentsController::actionToggleAdminHideComment` | mail | `emailTitleCommentAdminHidden`, `emailBodyCommentAdminHidden` |
| `cocreator.confirmation` | `Cocreator::sendConfirmation` | mail | `emailTitleConfirmCocreatorStatus`, `emailBodyConfirmCocreatorStatus` |
| `project.contact_message` | `TaskProposeController::actionContact` / `ContactForm::sendMessage` | mail | temat i treść z formularza publicznego |
| `user.activation.email` | `UserController::actionRegister` | mail | `emailTitleUserActivation`, `emailBodyUserActivation` |
| `user.activation.repeated_email` | `ActivationController::actionRepeated` | mail | `emailTitleUserActivation`, `emailBodyUserActivation` |
| `user.password_reset.email` | `UserController::actionPasswordReset` | mail | `emailTitleUserPasswordReset`, `emailBodyUserPasswordReset` |
| `voting.token.email` | `VotingController::sendTokentByEmail` | mail | `votingTokenEmailSubject`, widok `votingToken` |
| `voting.token.sms` | `VotingController::sendTokenBySms` | sms | `smsVotingToken` |
| `voting.summary.email` | `VotingController::sendVoteSummaryByEmail` | mail | `votingSummarySubject`, widok `voteSummary` |
| `voting.summary.sms` | `VotingController::sendVoteSummaryBySms` | sms | `smsVotingSummary` |
| `voting.summary.sms_failure_email` | `VotingController::sendVoteSummaryErrorEmail` | mail | treść hardcoded do administratora technicznego |

## Świadome braki na tym etapie

- Kolejka obejmuje bazowe powiadomienia projektu; nie wszystkie triggery z `LegacyCommunicationTrigger` mają jeszcze osobne akcje domenowe.
- Brak integracji z operatorem SMS dla powiadomień innych niż token głosowania i podsumowanie SMS.
- Publiczne komentarze mają domenowe akcje zgodne z `CommentsController`: dodanie przez rolę `applicant`, edycję i ukrycie własnego komentarza, akceptację administracyjną, ukrycie administracyjne oraz powiadomienia mailowe dla autora projektu/autora komentarza przez kolejkę Laravel.
- `ProjectPublicCommentVisibilityService` odtwarza warunki widoczności z legacy `_comments.php`: komentarz zaakceptowany i nieukryty jest publiczny; oczekujący widzi autor komentarza, admin i autor projektu; ukryty przez użytkownika widzi autor komentarza i admin; ukryty administracyjnie widzi tylko admin.
- Brak pełnego publicznego UI do dodawania i moderowania komentarzy; logika domenowa, import oraz testy są gotowe do podpięcia pod kontrolery/Livewire.

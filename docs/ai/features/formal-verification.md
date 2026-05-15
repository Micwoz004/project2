# Ocena formalna

1. Legacy: `TaskVerification`, `TaskController`, scenariusz `formalVerification`.
2. Tabele: `taskverification`, `tasks`, `taskdepartmentassignment`, `filesprivate`.
3. Dane wejściowe: lista poparcia, notatki, potrzeba korekty, potrzeba weryfikacji wstępnej, decyzje formalne.
4. Dane zapisywane: wynik formalny, komentarze, status projektu, przydziały.
5. Statusy: `DuringFormalVerification=10`, `FormallyVerified=3`, `RejectedFormally=-1`.
6. Walidacje: wymagane pola formalne, lista poparcia, decyzja o dalszym etapie.
7. Role: BDO/urzędnik formalny.
8. Edge case: brak listy poparcia, skierowanie do korekty, równoległy przydział departamentu.
9. Laravel: `formal_verifications`, `verification_assignments`, akcje statusów.
10. Zgodność: testy przejść statusów i wymaganych odpowiedzi.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 3.

1. [x] Spisać podstawowe pola formularza formalnego z legacy.
2. [x] Dodać akcję rozpoczęcia i zakończenia oceny formalnej.
3. [x] Dodać reguły pozytywnego/negatywnego wyniku.
4. [x] Dodać reguły korekty i przekazania dalej do weryfikacji wstępnej.
5. [ ] Zbudować Filament Page dla oceny formalnej.
6. [x] Pokryć testami wymagane odpowiedzi i statusy.
7. [x] Dodać import fixture dla `taskverification` z zachowaniem `raw_legacy_payload`.
8. [x] Dodać import fixture dla historycznego `verificationversion`.

## Rozpoznane reguły legacy

- `TaskVerification::beforeSave()` ustawia status formularza i projektu na `STATUS_FORMALLY_VERIFIED=3`, gdy `result=1`, albo `STATUS_REJECTED_FORMALLY=-1`, gdy wynik jest negatywny.
- `ProcessingController::actionFormalVerification()` po zapisie `TaskVerification` wywołuje `Task::changeStatus($model->status)`.
- `Task::checkRequired()` wymaga poprawnej listy poparcia przy pozytywnej weryfikacji formalnej.
- Przy negatywnej weryfikacji formalnej legacy wymaga pola `notes`, czyli uzasadnienia odrzucenia.
- `TaskVerification` ma odpowiedzi tak/nie/nie dotyczy (`VALUE_NO=0`, `VALUE_YES=1`, `VALUE_NOT_APPLICABLE=2`) i pola komentarzy do poszczególnych pytań.

## Zaimplementowany odpowiednik Laravel

- `FormalVerification` zapisuje dane w `formal_verifications`, z odpowiedziami formularza w JSON.
- `BeginFormalVerificationAction` przenosi projekt ze statusu `Submitted` do `DuringFormalVerification`.
- `CompleteFormalVerificationAction` zapisuje/aktualizuje kartę formalną i ustawia status projektu na `FormallyVerified` albo `RejectedFormally`.
- `RequestFormalCorrectionAction` uruchamia korektę formalną dla projektu złożonego albo będącego w weryfikacji formalnej, przełącza projekt do `DuringFormalVerification` i używa wspólnego mechanizmu `StartCorrectionAction`.
- `ForwardFormalVerificationToInitialVerificationAction` przekazuje projekt ze statusu `FormallyVerified` do `DuringInitialVerification`, ustawia `need_pre_verification=true` i tworzy przydziały `MeritInitial` dla wskazanych jednostek.
- `RecordVerificationVersionAction` zapisuje snapshot bieżącej karty formalnej do `verification_versions` z typem `FormalVerification=4`, zgodnie z mechanizmem `VerificationVersion::beforeSave()` w Yii.
- Pozytywny wynik wymaga `projects.is_support_list=true`.
- Negatywny wynik wymaga `result_comments`.
- `LegacyFixtureImportService` importuje historyczne `taskverification`, mapuje wspólne pola i zachowuje pełny rekord w `raw_legacy_payload`.
- `VerificationVersion` zachowuje surowy snapshot JSON legacy, typ karty, użytkownika oraz legacy ID karty weryfikacyjnej.

## Zgodność do sprawdzenia

- Uzupełnić pełną listę pytań formularza formalnego w Filament na podstawie `TaskVerification::attributeLabels()`.
- `needCorrection`, `needPreVerification`, `initialDepartments` i wersjonowanie kart formalnych są obsługiwane w domenie; UI zostaje do podpięcia.
- Podpiąć `VerificationVersion` do bieżących akcji zapisu kart, żeby nowe zmiany w Laravel tworzyły snapshoty tak jak `beforeSave()` w Yii.

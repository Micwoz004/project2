# Ocena merytoryczna

1. Legacy: `TaskInitialMeritVerification`, `TaskFinishMeritVerification`, `MeritVerificationController`.
2. Tabele: `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation`, `tasksdepartments`, `tasks`.
3. Dane wejściowe: odpowiedzi merytoryczne, opinie departamentów, konsultacje, koszt, lokalizacja, rekomendacje.
4. Dane zapisywane: wyniki, komentarze, przydziały, status projektu.
5. Statusy: `DuringInitialVerification=11`, `SentForMeritVerification=12`, `DuringMeritVerification=13`, `MeritVerificationAccepted=14`, `MeritVerificationRejected=-12`.
6. Walidacje: kompletność odpowiedzi, przypisanie departamentu, wymagane uzasadnienia negatywne.
7. Role: weryfikatorzy, departamenty, koordynatorzy, BDO.
8. Edge case: wiele departamentów, zwrot do uzupełnienia, zmiana kosztu, kolizja z korektą.
9. Laravel: `initial_merit_verifications`, `final_merit_verifications`, `consultation_verifications`.
10. Zgodność: testy formularzy i przejść statusów per legacy.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 3.

1. [x] Spisać podstawową logikę wstępnej, końcowej i konsultacyjnej oceny merytorycznej.
2. [x] Dodać przydziały departamentów i terminy.
3. [x] Dodać akcje zakończenia oceny z decyzją.
4. [ ] Udostępnić formularze w Filament według roli/departamentu.
5. [x] Pokryć testami przydziały, konsultacje, koszty i negatywne wyniki.
6. [ ] Uzupełnić pełne formularze pól legacy w UI/DTO.
7. [x] Dodać import fixture dla `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation` i `taskdepartmentassignment`.

## Rozpoznane reguły legacy

- `TaskDepartmentAssignment` ma typy: `TYPE_MERIT_INITIAL=1`, `TYPE_MERIT_FINISH=2`, `TYPE_CONSULTATION=3`, `TYPE_FORMAL_VERIFICATION=4`.
- `TaskInitialMeritVerification`, `TaskFinishMeritVerification` i `TaskConsultation` mają statusy kart: `STATUS_WORKING_COPY=1` oraz `STATUS_SENT=2`.
- Wysłanie karty ustawia `sendDate` i oznacza przydział jako zwrócony z wynikiem (`isReturned=0`, `sendDate=NOW()`).
- Karty weryfikacji są wersjonowane przez `VerificationVersion`; pełny odpowiednik wersjonowania zostaje do dopisania.
- Końcowa weryfikacja zapisuje `correctedCostJson` i `futureCostJson`; przy wysłaniu każde pole kosztu musi mieć opis i sumę.
- Konsultacja zapisuje wynik i komentarze, ale nie zmienia bezpośrednio statusu projektu w kontrolerze karty.

## Zaimplementowany odpowiednik Laravel

- `VerificationAssignment` odwzorowuje `taskdepartmentassignment`.
- `VerificationCardStatus` odwzorowuje statusy kart legacy.
- `InitialMeritVerification`, `FinalMeritVerification`, `ConsultationVerification` zapisują karty w istniejących tabelach domenowych.
- `AssignVerificationDepartmentAction` tworzy lub aktualizuje przydział departamentu dla konkretnego typu weryfikacji.
- `SubmitInitialMeritVerificationAction` wymaga przydziału przy wysyłce, zapisuje kartę i przenosi projekt do `SentForMeritVerification` albo `InitialVerificationRejected`.
- `SubmitFinalMeritVerificationAction` wymaga przydziału przy wysyłce, waliduje koszty i przenosi projekt do `MeritVerificationAccepted` albo `MeritVerificationRejected`.
- `SubmitConsultationVerificationAction` wymaga przydziału przy wysyłce i oznacza konsultację jako wysłaną bez zmiany statusu projektu.
- `LegacyFixtureImportService` importuje historyczne karty weryfikacji, konsultacje i przydziały, zapisując pełny rekord w `raw_legacy_payload`.

## Świadome uproszczenia na tym etapie

- Pełne listy pól pytań z formularzy legacy są przechowywane w `answers` JSON. Logika statusów i wymaganych danych jest w domenie; kompletne formularze UI zostaną odwzorowane później.
- Nie ma jeszcze odpowiednika `VerificationVersion` dla kart merytorycznych.
- Nie rozstrzygamy jeszcze automatycznie agregacji wielu departamentów. Każda karta i przydział działa per departament; końcowe reguły agregacji zostaną doprecyzowane przy module administracyjnej obsługi weryfikacji.

## Zgodność do sprawdzenia

- Porównać pełne payloady `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation` w imporcie legacy.
- Dopisać wersjonowanie kart i logikę zwrotu do kopii roboczej (`setAsReturned`).
- Uzupełnić reguły przejść statusów na poziomie koordynatora, gdy wiele departamentów ma przydziały równolegle.

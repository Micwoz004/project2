# Edycja projektu i korekty

1. Legacy: `TaskController`, `TaskCorrection`, `TaskChangesSuggestion`, pola korekty w `Task`.
2. Tabele: `tasks`, `taskcorrection`, `taskchangessuggestion`, `versions`, `files`, `taskcosts`.
3. Dane wejściowe: poprawione pola projektu, komentarze, akceptacja zmian, okno korekty.
4. Dane zapisywane: zmiany projektu, `correctionNo`, `correctionStartTime`, `correctionEndTime`, `needCorrection`, wersja.
5. Statusy: korekty występują po złożeniu i w etapach weryfikacyjnych; statusy `DuringChangesSuggestion=24` i `ChangesSuggestionAccepted=25`.
6. Walidacje: edycja tylko w stanie korekty lub kopii roboczej; zachowane walidacje formularza projektu.
7. Role: autor, BDO, weryfikatorzy zależnie od etapu.
8. Edge case: korekta po terminie, równoległa edycja, usunięte załączniki, brak zgody na zmianę.
9. Laravel: rozbudować akcje `StartCorrectionAction`, `ApplyCorrectionAction`, `AcceptChangesSuggestionAction`.
10. Zgodność: scenariusze statusów i wersji porównać z `TaskController`.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 1.

1. [x] Dodać serwis `ProjectLifecycleService` dla blokad edycji.
2. [x] Dodać akcje rozpoczęcia i zakończenia korekty.
3. [x] Wymusić wersjonowanie każdej korekty.
4. [ ] Udostępnić korekty w Filament i publicznym panelu autora.
5. [x] Pokryć testami okno korekty, statusy i whitelistę pól.
6. [ ] Dopisać obsługę załączników/kosztów w korektach po implementacji pełnych uploadów.

## Rozpoznane reguły legacy

- `TaskCorrection` przechowuje flagi pól możliwych do poprawy: tytuł, obszar, lokalizacja, mapa, cel, opis, uzasadnienie, dostępność, kategoria, odbiorcy, nieodpłatność, koszt oraz typy załączników.
- `TaskCorrection::correctionIsAllowed()` dopuszcza korektę tylko gdy istnieje niewykonana korekta z `correctionDeadline > dzisiaj`.
- `Task::getIsCorrectionAllowed()` dla pól `tasks.correctionStartTime/correctionEndTime` dopuszcza autora tylko pomiędzy startem i końcem okna.
- `TaskController` usuwa z payloadu każde pole, którego nie ma w `TaskCorrection::getAttributesToValidate()`, co chroni przed odblokowaniem pól przez JavaScript.
- Domyślny termin korekty w legacy jest liczony jako 5 dni roboczych z deadline traktowanym granicznie.

## Zaimplementowany odpowiednik Laravel

- `project_corrections` odwzorowuje `taskcorrection`, zachowuje `legacy_id`, `allowed_fields`, `notes`, `correction_deadline` i `correction_done`.
- `ProjectCorrectionField` jawnie opisuje whitelistę pól legacy.
- `StartCorrectionAction` tworzy korektę, blokuje korektę kopii roboczej, odrzuca pustą listę pól, podbija `correction_no` i ustawia okno korekty na projekcie.
- `ApplyCorrectionAction` dopuszcza tylko aktywne okno, filtruje payload do pól wskazanych w korekcie, uruchamia walidator składania projektu i zapisuje snapshot w `project_versions`.
- Po poprawnym zastosowaniu korekty aktywne okno jest zamykane przez `correction_done=true` i wyczyszczenie flagi `need_correction`.

## Zgodność do sprawdzenia

- W imporcie porównać historię `taskcorrection` z `project_corrections`, w tym terminy i wykonanie.
- Po pełnym module plików dopisać obsługę korekty załączników: lista poparcia, zgoda właściciela, mapa, zgoda rodzica i inne załączniki.
- Po module kosztorysów rozszerzyć korekty kosztów tak, żeby wymagały minimum jednej pozycji jak legacy.

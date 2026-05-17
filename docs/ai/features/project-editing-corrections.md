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

Status: zaimplementowane dla pól obsługiwanych przez legacy `taskcorrection`; różnice końcowe wymagają porównania liczności po pełnym imporcie.

1. [x] Dodać serwis `ProjectLifecycleService` dla blokad edycji.
2. [x] Dodać akcje rozpoczęcia i zakończenia korekty.
3. [x] Wymusić wersjonowanie każdej korekty.
4. [x] Udostępnić administracyjne korekty w Filament.
5. [x] Udostępnić korekty w publicznym panelu autora.
6. [x] Pokryć testami okno korekty, statusy i whitelistę pól.
7. [x] Dopisać obsługę załączników w korektach po implementacji pełnych uploadów.

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
- `LegacyFixtureImportService` przenosi `taskcorrection`, mapując flagi kolumn legacy na `ProjectCorrectionField`, zachowując notatkę, deadline, autora i flagę wykonania.
- `project_change_suggestions` odwzorowuje `taskchangessuggestion`, przechowując stare i nowe dane projektu, kosztów i plików jako JSON oraz decyzję autora/admina.
- `LegacyFixtureImportService` przenosi `taskchangessuggestion` do `project_change_suggestions`, zachowując deadline, konsultację, komentarz autora i decyzję.
- `DecideProjectChangeSuggestionAction` odtwarza `TaskChangesSuggestion::afterSave`: akceptacja aktualizuje pola projektu, podmienia kosztorys i opisy plików oraz ustawia `ChangesSuggestionAccepted`; odrzucenie wraca do `DuringMeritVerification`.
- `ProjectChangeSuggestionResource` pokazuje propozycje zmian zaimportowane z `taskchangessuggestion` i pozwala operatorowi z `project_corrections.manage`, `projects.manage` albo rolą `admin`/`bdo` rozstrzygnąć je przez `DecideProjectChangeSuggestionAction`. UI nie aktualizuje projektu bezpośrednio.
- `LegacyFixtureImportService` przenosi historyczne rekordy `versions` do `project_versions`, zachowując `legacy_id`, JSON pól projektu, plików i kosztów oraz czas utworzenia wersji.
- Po poprawnym zastosowaniu korekty aktywne okno jest zamykane przez `correction_done=true` i wyczyszczenie flagi `need_correction`.
- `ProjectResource` w Filament udostępnia administracyjne wezwanie do korekty z whitelistą pól oraz zastosowanie korekty przez `ApplyCorrectionAction`. Formularz administracyjny obejmuje pola projektu wspierane przez `ProjectCorrectionField::editableProjectColumns()`.
- Publiczne trasy `/moje-projekty/{project}/korekta` pozwalają autorowi w aktywnym oknie korekty poprawić tylko pola odblokowane w `project_corrections.allowed_fields`.
- `UpdatePublicProjectCorrectionRequest` waliduje tylko pola odblokowane w aktywnej korekcie. To odtwarza legacy `TaskController`, który przed walidacją usuwał z `Task` każde pole spoza `TaskCorrection::getAttributesToValidate()`.
- Korekta kategorii synchronizuje także pivot `category_project`, żeby publiczna kategoria główna i wielokategorie nie rozjechały się po poprawce.
- Korekta kosztu przez pole legacy `cost` podmienia pozycje `project_cost_items`, przelicza `projects.cost_formatted`, aktualizuje tekstowe `projects.cost` i nadal wymaga co najmniej jednej pozycji kosztorysu.
- Korekty załączników obsługują pola legacy `support_attachment`, `agreement_attachment`, `map_attachment`, `parent_agreement_attachment` i `attachments`; pliki są zapisywane przez `StoreProjectFileAction`, a sama korekta może zostać zamknięta nawet bez zmiany pól tekstowych.

## Zgodność do sprawdzenia

- Porównać pełne liczności `taskcorrection` z `project_corrections` po docelowym imporcie z dumpa MySQL.
- Układ publicznego formularza korekty można dopracować wizualnie po wdrożeniu, ale logika biznesowa whitelisty, załączników, kosztów i wersji jest po stronie domeny.

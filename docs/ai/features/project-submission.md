# Składanie projektu

1. Legacy: `TaskProposeController`, `TaskController`, `Task::rules`, widoki `views/taskPropose` i `views/task`.
2. Tabele: `tasks`, `taskcosts`, `files`, `filesprivate`, `cocreators`, `versions`, `taskgroups`, `tasktypes`, `categories`.
3. Dane wejściowe: tytuł, lokalizacja, opis, cel, uzasadnienie, dostępność, odbiorcy, bezpłatność, obszar, kategorie, autor, koszty, załączniki, zgody.
4. Dane zapisywane: projekt, kosztorys, załączniki, współautorzy, wersja i status.
5. Statusy: start `WorkingCopy=1`, po złożeniu `Submitted=2`.
6. Walidacje: wymagane pola z `Task::rules`, zakaz URL w polach opisowych, limity długości, lista poparcia, minimum jeden koszt, typy/limity plików.
7. Role: publiczny autor/wnioskodawca; administracja widzi i obsługuje zgłoszenie po złożeniu.
8. Edge case: projekt z URL, brak kosztu, brak listy poparcia, projekt poza oknem składania, duże pliki, brak zgód.
9. Laravel: `SubmitProjectAction`, `Project`, `ProjectCostItem`, `ProjectFile`, publiczne `/projekty/zglos`.
10. Zgodność: testy dla złożenia, URL, kosztów, listy poparcia i statusu; porównanie z formularzem Yii.

## Plan wdrożenia

Status: częściowo zaimplementowane; publiczne zgłoszenie zapisuje podstawowy projekt, koszt, kategorię, mapę, listę poparcia i współautorów.

1. Przenieść walidację wejścia publicznego do Form Request. Wykonane: `StorePublicProjectRequest`.
2. Wyodrębnić walidację domenową z akcji składania projektu. Wykonane: `ProjectSubmissionValidator`.
3. Dodać policy dla widoczności, edycji i składania projektu. Wykonane: `ProjectPolicy` i `ProjectLifecycleService`.
4. Rozszerzyć formularz o autorów, kategorie, mapę i prawdziwe uploady. Wykonane częściowo: publiczny formularz zapisuje realny upload listy poparcia jako prywatny `ProjectFile`, zapisuje kategorię główną i pivot kategorii projektu, zapisuje dane mapowe oraz synchronizuje maksymalnie dwóch współautorów.
5. Dodać testy: wymagane pola, URL, koszt, lista poparcia, status i wersja.

## Implementacja Laravel

- `StorePublicProjectRequest` wymaga pliku `support_list_file` na granicy HTTP.
- `StorePublicProjectRequest` przyjmuje `address`, `plot`, `lat`, `lng`, `map_lng_lat` i `map_data`; JSON mapy jest dekodowany na granicy requestu i zapisywany w castowanym polu `projects.map_data`.
- `StorePublicProjectRequest` wymaga kategorii projektu; `PublicProjectController::store()` zapisuje ją w `projects.category_id` i synchronizuje pivot `category_project`, żeby zachować zgodność z raportami po kategoriach.
- `PublicProjectController::store()` zapisuje listę poparcia przez `StoreProjectFileAction` przed złożeniem projektu i oznacza plik jako `is_task_form_attachment`.
- Lista poparcia z publicznego formularza jest prywatna (`is_private=true`), zgodnie z ostrożnym odwzorowaniem danych wrażliwych z legacy.
- `StorePublicProjectRequest::coauthors()` filtruje puste sloty współautorów i przekazuje dane do `SyncProjectCoauthorsAction`, która wymusza reguły legacy `Cocreator`.

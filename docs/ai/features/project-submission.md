# Składanie projektu

1. Legacy: `TaskProposeController`, `TaskController`, `Task::rules`, widoki `views/taskPropose` i `views/task`.
2. Tabele: `tasks`, `taskcosts`, `files`, `filesprivate`, `cocreators`, `versions`, `taskgroups`, `tasktypes`, `categories`.
3. Dane wejściowe: tytuł, lokalizacja, opis, cel, uzasadnienie, dostępność, odbiorcy, bezpłatność, obszar, kategorie, autor, koszty, załączniki, zgody.
4. Dane zapisywane: projekt, kosztorys, załączniki, współautorzy, wersja i status.
5. Statusy: start `WorkingCopy=1`, po złożeniu `Submitted=2`.
6. Walidacje: wymagane pola z `Task::rules`, zakaz URL w polach opisowych, limity długości, lista poparcia, minimum jeden koszt, limit kosztów obszaru, typy/limity plików.
7. Role: publiczny autor/wnioskodawca; administracja widzi i obsługuje zgłoszenie po złożeniu.
8. Edge case: projekt z URL, brak kosztu, brak listy poparcia, projekt poza oknem składania, duże pliki, brak zgód.
9. Laravel: `SubmitProjectAction`, `Project`, `ProjectCostItem`, `ProjectFile`, publiczne `/projekty/zglos`.
10. Zgodność: testy dla złożenia, URL, kosztów, listy poparcia i statusu; porównanie z formularzem Yii.

## Plan wdrożenia

Status: częściowo zaimplementowane; publiczne zgłoszenie zapisuje projekt, autora jako snapshot danych formularza, kosztorys wielowierszowy, kategorię, mapę, zgody, załączniki legacy i współautorów.

1. Przenieść walidację wejścia publicznego do Form Request. Wykonane: `StorePublicProjectRequest`.
2. Wyodrębnić walidację domenową z akcji składania projektu. Wykonane: `ProjectSubmissionValidator`.
3. Dodać policy dla widoczności, edycji i składania projektu. Wykonane: `ProjectPolicy` i `ProjectLifecycleService`.
4. Rozszerzyć formularz o autorów, kategorie, mapę i prawdziwe uploady. Wykonane częściowo: publiczny formularz zapisuje realny upload listy poparcia jako prywatny `ProjectFile`, przyjmuje pozostałe typy załączników legacy, zapisuje kategorię główną i pivot kategorii projektu, zapisuje typ projektu `local`, dane mapowe, snapshot autora, zgody autora, pole kontaktu oraz synchronizuje maksymalnie dwóch współautorów.
5. Dodać testy: wymagane pola, URL, koszt, lista poparcia, status i wersja.

## Implementacja Laravel

- `StorePublicProjectRequest` wymaga pliku `support_list_file` na granicy HTTP.
- `StorePublicProjectRequest` wymaga danych autora z widoku Yii: imię, nazwisko, e-mail, wybór co najmniej jednej publicznej formy kontaktu, potwierdzenie regulaminu oraz wybór `contact_with` (`1=autor`, `2=współautor`).
- Publiczny formularz zapisuje snapshot autora w `projects.authors`. To świadoma różnica względem legacy: w Yii dane autora były pobierane bezpośrednio z konta `users`; w nowym baseline publiczny endpoint może działać również bez sesji użytkownika, więc dane formularza nie są tracone.
- `StorePublicProjectRequest` przyjmuje `address`, `plot`, `lat`, `lng`, `map_lng_lat` i `map_data`; JSON mapy jest dekodowany na granicy requestu i zapisywany w castowanym polu `projects.map_data`.
- `map_data` jest wymagane przy złożeniu zgodnie z `Task::rules` dla scenariusza `propose`.
- Formularz zapisuje `short_description`, `additional_cost`, `local`, `contact_with`, `consent_to_change`, `attachments_anonymized` i `show_task_coauthors`.
- Kosztorys publiczny przyjmuje listę `cost_items[]`, zapisuje pozycje w `project_cost_items`, a w `projects.cost` i `projects.cost_formatted` utrzymuje zagregowany odpowiednik pól legacy `cost`/`costFormatted`.
- `ProjectCostLimitService` odtwarza serwerowo regułę z formularza Yii: koszt projektu nie może przekroczyć limitu obszaru (`cost_limit`, z fallbackiem na `cost_limit_big`), a mały projekt bez limitu szczegółowego przyjmuje historyczne `10 000`.
- Przy `local=2` (`Projekt Zielonego SBO`) zgłoszenie jest rozwiązywane do obszaru ogólnomiejskiego (`is_local=false` albo symbol `OGM`), co odpowiada legacy `Task::beforeValidate()` ustawiającemu `TaskType::CITY_WIDE_TASK_ID`.
- `PublicProjectController::store()` zapisuje dodatkowe uploady przez `StoreProjectFileAction`: zgody właściciela, mapy, zgody rodzica/opiekuna oraz pozostałe załączniki z limitem i listą rozszerzeń z legacy.
- `StorePublicProjectRequest` wymaga kategorii projektu; `PublicProjectController::store()` zapisuje ją w `projects.category_id` i synchronizuje pivot `category_project`, żeby zachować zgodność z raportami po kategoriach.
- `PublicProjectController::store()` zapisuje listę poparcia przez `StoreProjectFileAction` przed złożeniem projektu i oznacza plik jako `is_task_form_attachment`.
- Lista poparcia z publicznego formularza jest prywatna (`is_private=true`), zgodnie z ostrożnym odwzorowaniem danych wrażliwych z legacy.
- `StorePublicProjectRequest::coauthors()` filtruje puste sloty współautorów i przekazuje dane, także adresowe (`street`, `house_no`, `flat_no`, `post_code`, `city`), do `SyncProjectCoauthorsAction`, która wymusza reguły legacy `Cocreator`.
- Widok `resources/views/public/projects/create.blade.php` korzysta ze wspólnego publicznego design systemu: czytelny nagłówek, jednolity panel formularza, spójne pola, checkboxy i sekcje kosztów/współautorów bez zmiany walidacji HTTP ani domenowej.

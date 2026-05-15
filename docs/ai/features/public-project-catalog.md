# Publiczna lista projektów

1. Legacy: publiczne akcje `TaskController`, widoki list i szczegółów projektów.
2. Tabele: `tasks`, `tasktypes`, `categories`, `taskcosts`, `files`, `taskgroups`.
3. Dane wejściowe: filtry, wyszukiwarka, obszar, kategoria, edycja.
4. Dane zapisywane: brak, powierzchnia odczytowa.
5. Statusy: publicznie widoczne projekty zależą od statusu i flagi ukrycia; głosowanie używa `STATUS_PICKED`.
6. Walidacje: filtrowanie tylko po dozwolonych polach.
7. Role: anonimowy użytkownik publiczny.
8. Edge case: projekt ukryty, brak numeru losowania, brak obszaru, historyczna edycja.
9. Laravel: `PublicProjectController`, `/projekty`, `/projekt/{id}`, `/projekty-mapa`.
10. Zgodność: porównać liczbę i kolejność projektów z legacy dla tej samej edycji.

## Plan wdrożenia

Status: częściowo zaimplementowane.

1. Dodać policy widoczności projektu. Wykonane przez `ProjectPolicy` i `ProjectLifecycleService`.
2. Dodać filtry edycji, kategorii, obszaru i statusu publicznego. Wykonane w `PublicProjectCatalogQuery`.
3. Odtworzyć kolejność legacy po `numberDrawn`. Wykonane: projekty sortują się po `number_drawn`, potem `number`, potem `title`, z pustymi numerami na końcu.
4. Dodać widok mapy po potwierdzeniu danych `mapData`.
5. Pokryć testami ukryte projekty i zgodność filtrów. Wykonane dla widoczności, edycji, obszaru, kategorii i wyszukiwania.

## Implementacja

- Query: `App\Domain\Projects\Services\PublicProjectCatalogQuery`.
- Kontroler: `PublicProjectController@index`.
- Widok: `resources/views/public/projects/index.blade.php`.
- Testy: `PublicProjectCatalogTest`.

## Pozostałe różnice

- `/projekty-mapa` korzysta jeszcze z tej samej listy co `/projekty`; osobny widok mapy zostanie wdrożony po potwierdzeniu pełnego znaczenia legacy `mapData`, `mapLngLat`, `lat` i `lng`.

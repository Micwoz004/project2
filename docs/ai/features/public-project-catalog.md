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

Status: zaimplementowany baseline katalogu i mapy publicznej.

1. Dodać policy widoczności projektu. Wykonane przez `ProjectPolicy` i `ProjectLifecycleService`.
2. Dodać filtry edycji, kategorii, obszaru i statusu publicznego. Wykonane w `PublicProjectCatalogQuery`.
3. Odtworzyć kolejność legacy po `numberDrawn`. Wykonane: projekty sortują się po `number_drawn`, potem `number`, potem `title`, z pustymi numerami na końcu.
4. [x] Dodać widok mapy po potwierdzeniu danych `mapData`.
5. Pokryć testami ukryte projekty i zgodność filtrów. Wykonane dla widoczności, edycji, obszaru, kategorii i wyszukiwania.

## Implementacja

- Query: `App\Domain\Projects\Services\PublicProjectCatalogQuery`.
- Mapa: `App\Domain\Projects\Services\PublicProjectMapQuery`, która korzysta z tych samych filtrów co katalog i pokazuje tylko publiczne projekty z lokalizacją.
- Kontroler: `PublicProjectController@index`.
- Widoki: `resources/views/public/projects/index.blade.php`, `resources/views/public/projects/show.blade.php`, `resources/views/public/projects/map.blade.php`.
- Publiczny layout `resources/views/components/public/layout.blade.php` zawiera wspólny civic design system dla nawigacji, nagłówków, filtrów, kart projektów, tabel i stanów pustych. Layout nie zmienia logiki widoczności ani kolejności projektów.
- Testy: `PublicProjectCatalogTest`.
- Źródła współrzędnych mapy są sprawdzane w kolejności: `lat/lng`, pierwszy punkt z legacy `map_data`, a na końcu tekstowe `map_lng_lat` w formacie używanym przez formularz.

## Pozostałe różnice

- Aktualny widok mapy udostępnia dane projektów w JSON i tabeli z koordynatami; docelowa warstwa wizualna może podpiąć bibliotekę mapową bez zmiany logiki wyboru projektów i źródeł współrzędnych.

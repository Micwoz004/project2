# Obszary, kategorie i słowniki

1. Legacy: `TaskType`, `Category`, kontrolery `CategoryController`, słowniki imion/nazwisk.
2. Tabele: `tasktypes`, `categories`, `taskscategories`, `firstnamedictionary`, `lastnamedictionary`, `motherlastnamedictionary`.
3. Dane wejściowe: obszary, limity kosztów, kategorie, słowniki normalizacyjne.
4. Dane zapisywane: obszary, kategorie, pivoty, dane słownikowe.
5. Statusy: brak bezpośrednich statusów, ale obszar wpływa na głosowanie.
6. Walidacje: wymagany obszar, limity kategorii, lokalny/ogólnomiejski.
7. Role: administrator słowników.
8. Edge case: obszar historyczny, zmiana limitu po zgłoszeniach, projekt bez kategorii.
9. Laravel: `ProjectArea`, `Category`, pivot `category_project`.
10. Zgodność: import słowników i porównanie filtrów publicznych.

## Plan wdrożenia

Status: częściowo zaimplementowane.

1. Dodać Filament Resources dla obszarów i kategorii. Wykonane: `ProjectAreaResource`, `CategoryResource`.
2. Dodać walidacje limitów i typu obszaru. Wykonane w formularzach Filament zgodnie z legacy `TaskType::rules` i `Category::rules`: wymagane `name`, `symbol`, limity `64`, `8`, `50`.
3. Dodać import słowników imion/nazwisk.
4. Podpiąć kategorie do formularza projektu.
5. Pokryć testami limity kategorii i filtry. Częściowo wykonane: testy listy obszarów lokalnych, OGM, pivotu kategorii i rejestracji zasobów admina.

## Implementacja

- Obszary: `App\Filament\Resources\ProjectAreas\ProjectAreaResource`.
- Kategorie: `App\Filament\Resources\Categories\CategoryResource`.
- Logika listy obszarów: `App\Domain\Projects\Services\ProjectAreaCatalog`.
- Testy: `AreasCategoriesDictionariesTest`.

## Uwagi legacy

- `TaskType::CITY_WIDE_TASK_ID = 35` zostaje udokumentowane jako historyczny identyfikator, ale nowy system wykrywa obszar ogólnomiejski przez `is_local=false` albo symbol `OGM`.

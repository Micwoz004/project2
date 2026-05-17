# Obszary, kategorie i słowniki

1. Legacy: `TaskType`, `Category`, kontrolery `CategoryController`, słowniki imion/nazwisk.
2. Tabele: `tasktypes`, `categories`, `taskscategories`, `firstnamedictionary`, `lastnamedictionary`, `motherlastnamedictionary`.
3. Dane wejściowe: obszary, limity kosztów, kategorie, słowniki normalizacyjne.
4. Dane zapisywane: obszary, kategorie, pivoty, dane słownikowe.
5. Statusy: brak bezpośrednich statusów, ale obszar wpływa na głosowanie.
6. Walidacje: wymagany obszar, limity kategorii, lokalny/ogólnomiejski.
7. Role: administrator słowników.
8. Edge case: obszar historyczny, zmiana limitu po zgłoszeniach, projekt bez kategorii.
9. Laravel: `ProjectArea`, `Category`, pivot `category_project`, `DictionaryEntry`, `LegacyDictionaryImportService`.
10. Zgodność: import słowników i porównanie filtrów publicznych.

## Plan wdrożenia

Status: zaimplementowany baseline domenowy i administracyjny.

1. [x] Dodać Filament Resources dla obszarów i kategorii: `ProjectAreaResource`, `CategoryResource`.
2. [x] Dodać walidacje limitów i typu obszaru w formularzach Filament zgodnie z legacy `TaskType::rules` i `Category::rules`: wymagane `name`, `symbol`, limity `64`, `8`, `50`.
3. [x] Dodać import słowników imion/nazwisk.
4. [x] Podpiąć kategorie do formularza projektu.
5. [x] Pokryć testami limity kategorii, filtry i zasoby administracyjne.
6. [x] Dodać administracyjny resource dla słowników legacy.

## Implementacja

- Obszary: `App\Filament\Resources\ProjectAreas\ProjectAreaResource`.
- Kategorie: `App\Filament\Resources\Categories\CategoryResource`.
- Logika listy obszarów: `App\Domain\Projects\Services\ProjectAreaCatalog`.
- Słowniki legacy: `DictionaryEntry` konsoliduje `firstnamedictionary`, `lastnamedictionary`, `motherlastnamedictionary`.
- Import słowników: `LegacyDictionaryImportService` jest idempotentny po `source_table + legacy_id`, ponieważ legacy tabele mają niezależne identyfikatory.
- Panel Filament udostępnia `DictionaryEntryResource` dla `dictionary_entries`; dostęp i CRUD są chronione przez `DictionaryEntryPolicy` oraz `dictionaries.manage` albo role `admin`/`bdo`.
- Import fixture przenosi `taskscategories` do pivotu `category_project`, zachowując wiele kategorii projektu.
- `ProjectCostLimitService` wykorzystuje limity obszaru przy składaniu projektu i rozwiązuje `local=2` do obszaru ogólnomiejskiego bez opierania się na historycznym ID `35`.
- Testy: `AreasCategoriesDictionariesTest`, `LegacyDictionaryImportTest`.

## Uwagi legacy

- `TaskType::CITY_WIDE_TASK_ID = 35` zostaje udokumentowane jako historyczny identyfikator, ale nowy system wykrywa obszar ogólnomiejski przez `is_local=false` albo symbol `OGM`.
- Legacy `TaskType::returnCostLimit()` i JS formularza używały limitu kosztów obszaru jako blokady złożenia. Nowy system wykonuje tę walidację domenowo w `ProjectSubmissionValidator`.
- Słowniki imion/nazwisk są normalizowane do wielkich liter tak jak porównania legacy dla danych wyborców.

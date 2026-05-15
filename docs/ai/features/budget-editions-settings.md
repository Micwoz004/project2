# Edycje SBO i ustawienia

1. Legacy: `TaskGroup`, `TaskGroupController`, `SettingsForm`, `Page`.
2. Tabele: `taskgroups`, `settings`, `pages`, `yiicache`.
3. Dane wejściowe: daty procesu, treści publiczne, ustawienia systemu.
4. Dane zapisywane: edycja, strony, ustawienia.
5. Statusy: wynik `TaskGroup::getState`.
6. Walidacje: kolejność dat i brak nakładania edycji.
7. Role: administrator/BDO.
8. Edge case: granice dat legacy są inkluzywne i mają specyficzne zachowanie w `proposeStart >= now`.
9. Laravel: `BudgetEdition`, `BudgetEditionStateResolver`, `BudgetEditionResource`.
10. Zgodność: testy harmonogramu i walidacji dat.

## Plan wdrożenia

Status: częściowo zaimplementowane.

1. Dodać Form Request/akcję zapisu edycji z walidacją dat. Wykonane jako domenowy `BudgetEditionScheduleValidator` podpięty do zapisu Filament.
2. Dodać blokadę nakładania edycji. Wykonane 1:1 według legacy: inna edycja z `resultAnnouncementEnd > proposeStart` blokuje zapis.
3. Dodać strony treści tworzone po edycji. Wykonane jako `EnsureContentPagesForBudgetEditionAction` z symbolami legacy `V,S,A,I,TY,W,T`.
4. Dodać ustawienia systemowe i cache.
5. Pokryć testami granice dat zgodne z legacy. Wykonane dla granic stanu, kolejności dat, overlapu i stron treści.

## Uwagi 1:1

Legacy nie waliduje wszystkich możliwych relacji między końcem weryfikacji przed głosowaniem a startem głosowania. Nowy walidator zachowuje ten zakres 1:1: sprawdza tylko pary sprawdzane w `TaskGroup::afterValidate`.

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
3. Dodać strony treści tworzone po edycji. Wykonane jako `EnsureContentPagesForBudgetEditionAction` z symbolami legacy `V,S,A,I,TY,W,T`; import fixture przenosi także istniejące rekordy `pages` z HTML.
4. Dodać ustawienia systemowe i cache. Wykonane: `ApplicationSetting` i import fixture przenoszą `settings.category`, `settings.key` oraz surowe `settings.value`; `ApplicationSettings` udostępnia odczyt raw/string/int/bool bez parsowania serializacji Yii/PHP.
5. Pokryć testami granice dat zgodne z legacy. Wykonane dla granic stanu, kolejności dat, overlapu i stron treści.

## Uwagi 1:1

Legacy nie waliduje wszystkich możliwych relacji między końcem weryfikacji przed głosowaniem a startem głosowania. Nowy walidator zachowuje ten zakres 1:1: sprawdza tylko pary sprawdzane w `TaskGroup::afterValidate`.

Wartości `settings.value` są na etapie importu zachowywane dosłownie. To pozwala porównać dane 1:1 z dumpem; typed access w `ApplicationSettings` czyta proste wartości `string`, `int` i `bool`, ale nie deserializuje złożonych payloadów Yii/PHP.

Legacy `PageForm` zapisuje treści stron procesu głosowania bez dodatkowych walidacji biznesowych, a `Page::getPageBySymbol(Page::SYMBOL_VOID)` pobiera treść braku procesu z ustawienia `owner.pageProcessAbsence`. Rekordy `pages` dla konkretnych edycji są importowane do `content_pages`; `ContentPageResolver` odtwarza fallback symbolu `V` z ustawień, a pozostałe symbole czyta z `content_pages` wybranej edycji.

Legacy `Statuses` przechowuje edytowalne nazwy statusów projektu niezależnie od kodu statusu. Import zapisuje je w `project_status_labels`, a logika domenowa nadal korzysta z `ProjectStatus`.

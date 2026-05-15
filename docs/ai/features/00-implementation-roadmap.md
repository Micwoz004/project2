# Plan wdrożenia funkcji

Ten plik jest operacyjną mapą wdrożenia funkcjonalności opisanych w `docs/ai/features`. Każdy etap kończy się aktualizacją dokumentacji, testami oraz listą świadomych różnic względem legacy.

## Zasady etapowania

- Najpierw przenosimy reguły krytyczne do domeny i testów.
- Dopiero potem rozbudowujemy Filament i publiczne UI.
- Import legacy jest wdrażany iteracyjnie, po ustaleniu stabilnego modelu docelowego dla danego modułu.
- Raporty i eksporty powstają po potwierdzeniu źródeł danych dla głosowania, projektów i weryfikacji.

## Etap 1: fundament projektu i edycji

Status: w toku; `project-submission`, część `budget-editions-settings`, część `public-project-catalog`, część `areas-categories-dictionaries`, część `project-editing-corrections` i część `project-files-coauthors` są zaimplementowane.

Zakres:
- `budget-editions-settings`
- `project-submission`
- `project-editing-corrections`
- `project-files-coauthors`
- `public-project-catalog`
- `areas-categories-dictionaries`

Kryteria gotowości:
- kompletna domena projektów, edycji, obszarów i kategorii,
- statusy legacy jako enumy,
- walidacja zgłoszenia projektu poza UI,
- walidacja harmonogramu edycji 1:1 z `TaskGroup::afterValidate`,
- automatyczne utworzenie stron treści edycji według `Page::$symbols`,
- publiczne filtrowanie projektów po edycji, obszarze, kategorii i wyszukiwaniu,
- administracyjne zasoby Filament dla obszarów i kategorii,
- blokady edycji/korekt w akcjach domenowych,
- tabela korekt z whitelistą pól i wersjonowaniem po korekcie,
- domenowe limity załączników i walidacja współautorów,
- publiczny katalog projektów,
- testy zgłoszenia, korekt, widoczności i walidacji.

## Etap 2: administracja i RBAC

Status: w toku; bazowa mapa RBAC, synchronizacja Spatie, dostęp do Filament i policies dla edycji/słowników są zaimplementowane.

Zakres:
- `admin-rbac`
- `users-roles-auth`
- zasoby Filament dla projektów, edycji, obszarów, kategorii, użytkowników i ról.

Kryteria gotowości:
- częściowy import ról z `authitem*`,
- Spatie Permission z mapą uprawnień,
- Laravel Policies dla bazowych operacji administracyjnych,
- Filament Resources korzystające z domeny,
- testy policies i widoczności akcji.

## Etap 3: weryfikacje projektu

Status: w toku; bazowe rozpoczęcie i zakończenie oceny formalnej oraz domenowa logika weryfikacji merytorycznej są zaimplementowane.

Zakres:
- `formal-verification`
- `merit-verification`
- `notifications-correspondence-comments`

Kryteria gotowości:
- formularze formalne i merytoryczne odwzorowane z legacy,
- bazowe przejścia statusów formalnych zgodne z `TaskVerification::beforeSave`,
- przydziały departamentów i statusy kart weryfikacji merytorycznej,
- wynik wstępnej/końcowej oceny merytorycznej zmienia status projektu,
- przydziały departamentów,
- statusy i korekty wynikające z ocen,
- komentarze, korespondencja i powiadomienia,
- testy przejść statusów i wymaganych odpowiedzi.

## Etap 4: rady, komisje i odwołania

Status: w toku; podstawowa logika głosów ZK/OT/AT i decyzji jest zaimplementowana.

Zakres:
- `board-appeal-voting`

Kryteria gotowości:
- jeden model głosowań rad/komisji,
- blokada jednego głosu per użytkownik/projekt/typ,
- rozstrzyganie wyników i odwołań zgodne z podstawowymi akcjami legacy,
- testy remisów, odrzuceń i przejść statusów.

## Etap 5: głosowanie publiczne

Status: częściowy baseline domenowy istnieje, pełny flow zaplanowany.

Zakres:
- `public-voting`
- `vote-card-admin`

Kryteria gotowości:
- pełny Livewire flow głosowania,
- SMS token 6 cyfr i limit 5 SMS,
- PESEL, hash `newverification`, zgody rodzica i oświadczenia,
- karty papierowe/elektroniczne,
- admin kart głosowania,
- testy transakcji, duplikatów, limitów i statusów.

## Etap 6: wyniki, raporty i import

Status: częściowy baseline wyników istnieje.

Zakres:
- `results-calculation`
- `reports-exports`
- `legacy-import`

Kryteria gotowości:
- wyniki liczone tylko z ważnych kart,
- remisy i publikacja wyników,
- raporty publiczne i administracyjne,
- import dumpa z `legacy_id`,
- testy na fixture z legacy i porównania liczności.

## Kolejność najbliższej implementacji

1. `project-submission`: Form Request, walidator domenowy, policy i testy.
2. `admin-rbac`: pierwsza mapa ról i permissions. (wykonane częściowo)
3. `project-editing-corrections`: akcje korekty i blokady edycji. (wykonane częściowo)
4. `areas-categories-dictionaries`: import słowników imion/nazwisk.
5. `public-project-catalog`: osobny widok mapy po doprecyzowaniu legacy map.

# Plan wdrożenia funkcji

Ten plik jest operacyjną mapą wdrożenia funkcjonalności opisanych w `docs/ai/features`. Każdy etap kończy się aktualizacją dokumentacji, testami oraz listą świadomych różnic względem legacy.

## Zasady etapowania

- Najpierw przenosimy reguły krytyczne do domeny i testów.
- Dopiero potem rozbudowujemy Filament i publiczne UI.
- Import legacy jest wdrażany iteracyjnie, po ustaleniu stabilnego modelu docelowego dla danego modułu.
- Raporty i eksporty powstają po potwierdzeniu źródeł danych dla głosowania, projektów i weryfikacji.

## Etap 1: fundament projektu i edycji

Status: zrealizowany w baseline logiki. Pozostaje tylko finalny przegląd tekstów oświadczeń względem aktualnych materiałów urzędowych przed wdrożeniem produkcyjnym.

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

Status: zrealizowany w baseline logiki. Mapa RBAC, import ról, policies, dostęp do Filament oraz zasoby administracyjne dla podstawowych słowników i użytkowników są wdrożone.

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

Status: zrealizowany w baseline logiki. Formularze formalne/merytoryczne, statusy, przydziały, wersjonowanie, korespondencja, komentarze, mailowe triggery legacy i publiczna moderacja komentarzy są wdrożone.

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

Status: zrealizowany w baseline logiki. Głosy ZK/OT/AT, restarty/zamknięcia, rozstrzygnięcia, odwołania i akcje Filament są wdrożone.

Zakres:
- `board-appeal-voting`

Kryteria gotowości:
- jeden model głosowań rad/komisji,
- blokada jednego głosu per użytkownik/projekt/typ,
- rozstrzyganie wyników i odwołań zgodne z podstawowymi akcjami legacy,
- testy remisów, odrzuceń i przejść statusów.

## Etap 5: głosowanie publiczne

Status: zrealizowany w baseline logiki. Flow Livewire, tokeny SMS/e-mail, PESEL, rejestr hashy, zgody, karty papierowe, administracja kartami i testy krytyczne są wdrożone. Produkcyjne wartości operatora SMS pozostają konfiguracją środowiska.

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

Status: zrealizowany w baseline logiki. Wyniki, remisy, publikacja, dashboard Filament, raporty CSV/XLSX, kolejkowane eksporty oraz import fixture/staging/dump-count są wdrożone.

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

## Pozostałe przed produkcją

1. Finalnie porównać teksty oświadczeń formularza projektu z aktualną wersją urzędową.
2. Finalnie porównać etykiety formularza formalnego z aktualną wersją urzędową.
3. Ustawić produkcyjne wartości operatora SMS (`SMS_DRIVER=http`, URL, token, nadawca, timeout i treści szablonów).
4. Opcjonalnie odtworzyć graficzne szablony XLSX z `raporty_sbo`; dane domenowe raportów są już dostępne w CSV/XLSX.
5. Opcjonalnie dopracować mikrocopy i układ UI bez zmiany logiki domenowej.

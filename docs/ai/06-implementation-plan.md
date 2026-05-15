# Plan implementacji

## Kolejność migracji

1. Domknąć analizę legacy dla projektów i głosowania.
2. Uruchomić import fixture z wycinkiem dumpa.
3. Rozbudować formularz zgłoszenia projektu: autorzy, współautorzy, załączniki, mapa, zgody.
4. Dodać korekty projektu, wersjonowanie zmian i blokady statusów.
5. Zaimplementować pełny panel projektów w Filament.
6. Zaimplementować RBAC: import ról, permissions i policies.
7. Odtworzyć weryfikację formalną.
8. Odtworzyć weryfikacje merytoryczne, konsultacje i przydziały.
9. Odtworzyć głosowania rad/komisji i odwołania.
10. Zaimplementować pełny publiczny proces głosowania z tokenem SMS.
11. Odtworzyć wyniki, remisy i statusy po wynikach.
12. Odtworzyć raporty i eksporty.
13. Dodać testy regresyjne na danych legacy.
14. Przeprowadzić porównanie funkcjonalne z legacy.

## Najbliższe zadania techniczne

- Dodać seed/import małego zestawu danych legacy.
- Dodać policies dla `VoteCard`, `BudgetEdition` i pozostałych zasobów administracyjnych.
- Rozszerzyć `ProjectPolicy` o decyzje per rola po imporcie RBAC.
- Uzupełnić Filament Resources dla głosów, kart, użytkowników, ról, słowników i weryfikacji.
- Dodać kolejki dla wysyłek e-mail/SMS po ustaleniu integracji.

## Kryteria jakości

- Każda reguła krytyczna ma test.
- Każda świadoma różnica względem legacy jest opisana w `03-database-mapping.md` albo pliku feature.
- Logi operacji biznesowych mają start, sukces i przewidywalne odrzucenia.
- Kod domenowy nie powtarza walidacji z granic systemu bez potrzeby.

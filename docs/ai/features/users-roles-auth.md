# Użytkownicy, role i logowanie

1. Legacy: `User`, `UserController`, `LoginForm`, tabele Yii RBAC.
2. Tabele: `users`, `authitem`, `authitemchild`, `authassignment`, `departments`.
3. Dane wejściowe: konto, hasło, status, rola, departament.
4. Dane zapisywane: użytkownik, przypisania ról, departament.
5. Statusy: aktywny/nieaktywny użytkownik.
6. Walidacje: dane logowania, unikalność konta, aktywność i uprawnienia.
7. Role: wszystkie role legacy z `authitem`, zachowane w Spatie Permission z oryginalnymi nazwami.
8. Edge case: historyczne konto bez e-maila, wiele ról, brak departamentu.
9. Laravel: `User`, Spatie Permission, Filament auth.
10. Zgodność: import RBAC, relacje `authitemchild`, przypisania `authassignment` i test policies per operacja.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 2, rozszerzone o fixture import RBAC.

1. [x] Dodać mapę ról legacy do Spatie.
2. [x] Dodać import użytkowników i departamentów.
3. [x] Dodać policies i guard dostępu Filament.
4. [ ] Zbudować panel użytkowników i ról.
5. [x] Pokryć testami aktywność i uprawnienia dostępu do panelu.

## Aktualny zakres

- Model `User` implementuje kontrakt Filament i blokuje panel dla kont nieaktywnych.
- Seeder uruchamia synchronizację ról/uprawnień i nadaje lokalnemu użytkownikowi testowemu rolę `admin`.
- `SyncSystemRolesAndPermissionsAction` tworzy role legacy: `admin`, `analyst ODS`, `applicant`, `checkVoter`, `consultant`, `coordinator`, role ZK/ZOD/W JO oraz `bdo`.
- `LegacyUserImportService` importuje `departments` i `users` po `legacy_id`, zachowuje status aktywności i przypisuje departament przez `departments.legacy_id`.
- `LegacyRbacImportService` importuje `authitem`, `authitemchild` i `authassignment` do Spatie Permission na podstawie fixture, przypisując użytkowników przez `users.legacy_id`.

## Świadome różnice względem legacy

- Nowy kod używa stabilnych permission keys zamiast wyłącznie operacji tekstowych z Yii RBAC. Operacje legacy są nadal tworzone w Spatie, aby import danych mógł zachować oryginalne nazwy.
- Import użytkowników z fixture tworzy techniczny placeholder e-mail dla historycznych kont bez adresu, ponieważ Laravel wymaga unikalnego e-maila.
- Pełny import z dumpa pozostaje osobnym krokiem.

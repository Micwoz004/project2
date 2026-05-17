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
4. [x] Zbudować panel użytkowników i ról.
5. [x] Pokryć testami aktywność i uprawnienia dostępu do panelu.
6. [x] Odtworzyć legacy “usunięcie” konta jako anonimizację danych i odebranie ról.

## Aktualny zakres

- Model `User` implementuje kontrakt Filament i blokuje panel dla kont nieaktywnych.
- Seeder uruchamia synchronizację ról/uprawnień i nadaje lokalnemu użytkownikowi testowemu rolę `admin`.
- `SyncSystemRolesAndPermissionsAction` tworzy role legacy: `admin`, `analyst ODS`, `applicant`, `checkVoter`, `consultant`, `coordinator`, role ZK/ZOD/W JO oraz `bdo`.
- `LegacyUserImportService` importuje `departments` i `users` po `legacy_id`, zachowuje status aktywności i przypisuje departament przez `departments.legacy_id`; ten sam serwis jest używany przez import fixture i `sbo:legacy-import-mysql`.
- `LegacyRbacImportService` importuje `authitem`, `authitemchild` i `authassignment` do Spatie Permission, przypisując użytkowników przez `users.legacy_id`; ten sam graf RBAC działa dla fixture i bezpośredniego staging MySQL.
- `UserActivationToken` odwzorowuje legacy `activations` z typami: `1` aktywacja e-mail, `2` aktywacja SMS, `3` reset hasła.
- Legacy linki aktywacyjne i resetu hasła są ważne przez `system.activationLinkLifetime`; docelowa akcja auth UI musi zachować tę regułę.
- `LegacyPeselRecord` odwzorowuje administrowany rejestr `pesel` dostępny w legacy przez permission `manage pesel`.
- `LegacyPeselVerificationEntry` odwzorowuje whitelistę `verification`, którą legacy `User::verifyPeselAuthenticity` sprawdzało dla autentyczności PESEL.
- `UserResource` w Filament daje listę, tworzenie i edycję użytkowników, status aktywności, departament oraz przypisania ról Spatie. Dostęp do resource wymaga `users.manage` albo roli `admin`/`bdo`.
- Tworzenie i edycja kont synchronizują role przez `syncRoles`; puste hasło przy edycji nie nadpisuje istniejącego hasła.
- `AnonymizeUserAction` odtwarza `User::anonymize()` z Yii: konto zostaje dezaktywowane, login zaczyna się od `deleted-`, dane osobowe i adresowe są maskowane, departament jest czyszczony, hasło staje się technicznie nieużywalne, a role Spatie są usuwane.
- `EditUser` w Filament ma akcję “Anonimizuj konto” z potwierdzeniem; operacja wymaga `users.manage` albo roli `admin`/`bdo` i loguje tylko identyfikatory bez PII.

## Świadome różnice względem legacy

- Nowy kod używa stabilnych permission keys zamiast wyłącznie operacji tekstowych z Yii RBAC. Operacje legacy są nadal tworzone w Spatie, aby import danych mógł zachować oryginalne nazwy.
- Import użytkowników z fixture tworzy techniczny placeholder e-mail dla historycznych kont bez adresu, ponieważ Laravel wymaga unikalnego e-maila.
- Anonimizacja używa unikalnego adresu `deleted-{id}@anonymous.local` zamiast legacy `*`, ponieważ kolumna `users.email` w Laravel ma constraint unikalności i jest używana przez auth.
- Pełny import operacyjny wymaga odtworzenia dumpa do staging MySQL i uruchomienia `sbo:legacy-import-mysql`; parser surowego pliku `.sql` pozostaje opcjonalnym krokiem operacyjnym.
- Panel nie odtwarza ekranów Yii 1:1; zachowuje operacje biznesowe: aktywność konta, przypisanie departamentu oraz role/uprawnienia.

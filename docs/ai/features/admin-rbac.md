# Panel administracyjny i RBAC

1. Legacy: `UserController`, `authitem`, `authitemchild`, `authassignment`, kontrolery administracyjne.
2. Tabele: `users`, `authitem*`, `departments`, docelowo Spatie `roles`, `permissions`, pivoty.
3. Dane wejściowe: użytkownicy, role, uprawnienia, przypisania departamentów.
4. Dane zapisywane: konta, role, permissiony, departamenty.
5. Statusy: aktywność użytkownika i uprawnienia decydują o operacjach na projektach.
6. Walidacje: unikalny e-mail/login, aktywność użytkownika, uprawnienia do operacji.
7. Role: `admin`, `analyst ODS`, `applicant`, `checkVoter`, `consultant`, `coordinator`, obserwatorzy i przewodniczący ZK/ZOD/W JO, weryfikatorzy ZK/ZOD/W JO, BDO jako rola techniczna nowego panelu.
8. Edge case: użytkownik bez departamentu, historyczna rola, import nieznanej roli.
9. Laravel: Spatie Permission, policies, Filament Resources.
10. Zgodność: import `authitem*`, testy policies i porównanie menu/akcji.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 2, rozszerzone dla głosowania, raportów i akcji weryfikacji/korekt.

1. [x] Spisać role i permissions z `authitem*`.
2. [x] Przygotować bazową synchronizację do Spatie Permission.
3. [x] Dodać policies dla projektów, edycji budżetu i słowników.
4. [x] Ograniczyć dostęp do panelu Filament przez aktywność użytkownika i `admin.access`.
5. [x] Pokryć testami synchronizację RBAC, dostęp do panelu i odmowy dla słowników/edycji.
6. [x] Dodać pełny import `authitemchild` i `authassignment` z dumpa legacy.
7. [x] Dodać policies/bramki dla kart głosowania, wyników i raportów.
8. [x] Dodać bazowe permissions dla weryfikacji po pełnym domknięciu paneli.
9. [x] Ograniczyć poszczególne akcje Filament przez permissions.

## Rozpoznane legacy RBAC

Role z `authitem.type=2`: `admin`, `analyst ODS`, `applicant`, `checkVoter`, `consultant`, `coordinator`, `observer ZK`, `observer ZOD`, `president W JO`, `president ZK`, `president ZOD`, `verifier W JO`, `verifier ZK`, `verifier ZOD`, `vicepresident ZK`, `vicepresident ZOD`.

Operacje z `authitem.type=0`: `assign coordinator`, `assign verifier`, `back rejected`, `generate documents`, `generate propose`, `generate reports`, `manage pesel`, `manage settings`, `manage task groups`, `manage users`, `manage votecards`, `propose task`, `recommend W JO`, `update task`, `view tasks`.

Nowy system zachowuje nazwy legacy jako role/uprawnienia Spatie, ale decyzje domenowe opiera na stabilnych permission keys: `admin.access`, `projects.view`, `projects.manage`, `projects.verify`, `verification.formal.manage`, `verification.merit.manage`, `project_corrections.manage`, `budget_editions.manage`, `dictionaries.manage`, `users.manage`, `voting.manage`, `vote_cards.manage`, `results.view`, `reports.export`, `pesel.manage`, `settings.manage`.

## Zaimplementowany odpowiednik Laravel

- `SystemRole` opisuje role legacy i techniczną rolę `bdo`.
- `SystemPermission` opisuje docelowe uprawnienia i mapę operacji legacy do nowych permission keys.
- `SyncSystemRolesAndPermissionsAction` tworzy role i uprawnienia Spatie bez kasowania nieznanych historycznych ról.
- `LegacyRbacImportService` przenosi relacje `authitemchild` oraz przypisania `authassignment` dla użytkowników z `legacy_id`.
- Import RBAC rozwiązuje graf relacji legacy rekurencyjnie: `role -> role -> permission`, `role -> permission -> permission` oraz bezpośredne `authassignment` do operacji są spłaszczane do uprawnień Spatie, bo Spatie nie ma natywnej hierarchii permissionów jak Yii RBAC.
- `sbo:legacy-import-mysql` używa tego samego importera RBAC po odczycie tabel `authitem`, `authitemchild` i `authassignment` ze staging MySQL.
- `LegacyAuditLog` zachowuje historyczną tabelę `logs`: operatora, opcjonalny projekt, treść audytu, kontroler, akcję i czas operacji.
- `User::canAccessPanel()` dopuszcza tylko aktywnych użytkowników z `admin.access` albo rolą `admin`/`bdo`.
- Policies dla `BudgetEdition`, `ProjectArea`, `Category` i `VoteCard` blokują operacje użytkownikom bez dedykowanych uprawnień.
- Bramki `view-results` i `export-reports` używają `results.view` oraz `reports.export`.
- Akcje Filament dla weryfikacji formalnej używają `verification.formal.manage` z kompatybilnością dla `projects.verify/projects.manage`.
- Akcje Filament dla weryfikacji merytorycznej używają `verification.merit.manage` z kompatybilnością dla `projects.verify/projects.manage`.
- Akcje Filament dla korekt projektu używają `project_corrections.manage` z kompatybilnością dla `projects.manage`.

## Zgodność do sprawdzenia

- Porównać liczności pełnych przypisań `authitemchild` i `authassignment` po bezpośrednim imporcie z dumpa/staging.
- Po docelowym imporcie z dumpa potwierdzić, czy granularne permission keys wymagają dodatkowego mapowania dla historycznych niestandardowych ról.

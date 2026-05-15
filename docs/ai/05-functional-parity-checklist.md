# Checklist parytetu funkcjonalnego

## Rozpoznane

- [x] Technologia legacy i struktura katalogów.
- [x] Lista głównych kontrolerów i modeli.
- [x] Lista tabel z dumpa.
- [x] Statusy projektów.
- [x] Stan edycji SBO.
- [x] Podstawowe reguły składania projektu.
- [x] Podstawowe reguły głosowania elektronicznego.
- [x] Podstawowe reguły liczenia wyników.

## Zaimplementowany baseline

- [x] Laravel 13, Filament 5, Spatie Permission, Pest, Larastan.
- [x] Migracje domenowe z `legacy_id`.
- [x] Enumy statusów i głosowania.
- [x] Serwisy PESEL/hash/token/głosowanie/wyniki.
- [x] Publiczne trasy: `/projekty`, `/projekt/{id}`, `/projekty-mapa`, `/projekty/zglos`, `/glosowanie`, `/wyniki`, `/raporty-publiczne`.
- [x] Zasoby Filament: projekty i edycje.
- [x] Pierwsze testy krytycznej logiki.
- [x] Plan wdrożenia funkcji w `docs/ai/features/00-implementation-roadmap.md`.
- [x] Form Request dla publicznego zgłoszenia projektu.
- [x] Domenowy walidator składania projektu.
- [x] Policy widoczności, edycji i składania projektu.
- [x] Domenowa walidacja harmonogramu edycji SBO zgodna z `TaskGroup::afterValidate`.
- [x] Automatyczne utworzenie stron treści edycji według symboli legacy.
- [x] Publiczny katalog projektów filtrowany po edycji, obszarze, kategorii i wyszukiwaniu.
- [x] Testy katalogu publicznego dla widoczności, filtrów i kolejności.
- [x] Filament Resources dla obszarów i kategorii.
- [x] Testy listy obszarów lokalnych/OGM i relacji kategorii.
- [x] Bazowa mapa ról legacy i canonical permissions.
- [x] Synchronizacja Spatie Permission dla ról/uprawnień.
- [x] Dostęp do Filament ograniczony aktywnością użytkownika i `admin.access`.
- [x] Policies dla edycji budżetu oraz słowników.
- [x] Tabela i model korekt projektu z whitelistą pól legacy.
- [x] Akcje rozpoczęcia i zastosowania korekty z wersjonowaniem.
- [x] Domenowe reguły typów i limitów załączników.
- [x] Domenowe reguły współautorów i zgód.
- [x] Bazowe przejścia statusów w ocenie formalnej.
- [x] Przydziały departamentów dla weryfikacji merytorycznej.
- [x] Domenowe statusy kart weryfikacji merytorycznej.
- [x] Bazowe przejścia statusów wstępnej i końcowej oceny merytorycznej.
- [x] Domenowe głosowania ZK/OT/AT z unikalnością głosu.
- [x] Bazowe rozstrzyganie decyzji rad/komisji i odwołań.
- [x] Publiczne głosowanie: hash rejestru wyborców, oświadczenie, zgoda rodzica, brak PESEL jako `Verifying`.
- [x] SMS token: 6 cyfr, aktywacja po telefonie i kodzie, limit 5 kodów na telefon, unieważnienie poprzednich i zużytych tokenów PESEL.
- [x] Administracyjna zmiana statusu karty głosowania i wpływ na wyniki.
- [x] Agregacja wyników po projektach, obszarach i kategoriach z kart zaakceptowanych.
- [x] Publiczna publikacja wyników zależna od etapu edycji.
- [x] Bazowe raporty kart: statusy i demografia zaakceptowanych kart bez PII.
- [x] Publiczny eksport CSV wyników bez PII.
- [x] Policy/bramki dla kart głosowania, wyników i eksportów raportów.
- [x] Baseline importu fixture legacy: `legacy_id`, relacje, statusy, głosy i statystyki partii.
- [x] Baseline korespondencji i komentarzy projektu z uprawnieniami i odczytem.

## Do pełnego parytetu

- [ ] Pełny formularz projektu z uploadami i zgodami 1:1 względem widoków Yii.
- [ ] Fizyczny upload plików, storage prywatny/publiczny i anonimizacja załączników.
- [ ] Korekty projektu w UI autora/admina oraz korekty załączników/kosztów.
- [ ] Weryfikacja formalna z pełną listą pól legacy.
- [ ] Weryfikacje merytoryczne i konsultacje departamentów.
- [ ] Wersjonowanie kart weryfikacji merytorycznej i pełna agregacja wielu departamentów.
- [ ] Głos przewodniczącego ZK, restart/zamknięcie głosowań i policy dla ról rad/komisji.
- [ ] Pełny publiczny formularz głosowania z aktywacją tokenu SMS w UI.
- [ ] Filament Resource dla kart głosowania i ręcznych kart papierowych.
- [ ] Pełny import danych z dumpa MySQL do PostgreSQL.
- [ ] Raporty i eksporty administracyjne.
- [ ] Reguły remisów po pełnym potwierdzeniu legacy.
- [ ] Kolejki i szablony powiadomień mail/SMS z pełną mapą typów legacy.
- [ ] Pełny import przypisań ról/uprawnień z `authitemchild` i `authassignment`.
- [ ] Polityki Laravel dla każdej roli i operacji modułów weryfikacji/głosowania/raportów.

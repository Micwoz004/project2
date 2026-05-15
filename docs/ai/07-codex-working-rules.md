# Reguły pracy Codex

## Źródło prawdy

Jeśli reguła nie jest pewna, sprawdzaj w tej kolejności: kod legacy, dump SQL, widoki, kontrolery, modele, helpery, walidatory, konfigurację i raporty.

## Implementacja

- Logika biznesowa trafia do `app/Domain`.
- Filament jest warstwą administracyjną, nie miejscem logiki krytycznej.
- Publiczne kontrolery walidują wejście i delegują do domeny.
- Nie dodawaj zbędnych null-checków w środku domeny, jeśli kontrakt wejścia jest zwalidowany.
- Używaj importów i krótkich nazw klas, bez FQCN w kodzie.
- Loguj start/sukces operacji oraz przewidywalne odrzucenia.

## Dokumentacja

- Po każdym większym etapie aktualizuj `docs/ai`.
- Każda funkcjonalność ma plik w `docs/ai/features`.
- Nie zapisuj PII ani pełnych rekordów z dumpa.

## Testy

- Testy jednostkowe: enumy, harmonogram, PESEL, hashe, kalkulatory.
- Testy feature: projekty, role, statusy, głosowanie, wyniki, import.
- Przed zakończeniem etapu uruchamiaj `php artisan test` albo `vendor/bin/pest`.

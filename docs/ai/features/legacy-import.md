# Import legacy

1. Legacy: `ImporterController`, `db/*.sql`, dump `sbo2025_prod.sql`.
2. Tabele: wszystkie tabele domenowe z dumpa; priorytet: edycje, obszary, projekty, koszty, pliki, głosy, role.
3. Dane wejściowe: dump MySQL albo staging MySQL `legacy-mysql`.
4. Dane zapisywane: rekordy PostgreSQL z `legacy_id`, statystyki importu.
5. Statusy: mapowanie statusów projektu i kart bez zmiany wartości.
6. Walidacje: kompletność relacji, brak PII w logach, powtarzalność importu.
7. Role: operator migracji/administrator.
8. Edge case: brakujące FK w danych historycznych, rekordy osierocone, kodowanie znaków, duże pliki.
9. Laravel: `legacy_import_batches`, komendy Artisan i transakcje per moduł.
10. Zgodność: fixture z wycinkiem dumpa, liczność rekordów, mapowanie `legacy_id`.

## Plan wdrożenia

Status: zaplanowane w etapie 6, fixture wcześniej.

1. Przygotować staging MySQL z profilem `legacy-import`.
2. Dodać komendy Artisan importujące moduły w kolejności zależności.
3. Zapisywać statystyki w `legacy_import_batches`.
4. Dodać fixture z małym wycinkiem dumpa do testów.
5. Porównać liczność, `legacy_id`, statusy i relacje.

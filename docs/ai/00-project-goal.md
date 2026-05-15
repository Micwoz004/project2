# Cel projektu

Nowa aplikacja ma zastąpić legacy SBO Szczecin oparte o Yii 1.x i MySQL. Legacy jest źródłem prawdy dla logiki biznesowej, a nowa aplikacja ma zachować procesy, role, statusy, walidacje, głosowanie, wyniki, raporty i skutki operacji.

## Zakres docelowy

- Monolit Laravel 13 na PHP 8.5.
- Filament 5 dla administracji pod `/admin`.
- Blade/Livewire dla publicznych powierzchni.
- PostgreSQL 18 jako baza główna.
- Redis dla cache i kolejek.
- Pest/PHPUnit dla krytycznej logiki domenowej.
- Spatie Permission jako odwzorowanie legacy RBAC `authitem*`.

## Źródła legacy

- Kod: `/Users/michalwozniak/Praca/zet.WIBO/zet.WIBO-szczecin/web/protected`.
- Dump bazy: `/Users/michalwozniak/Praca/zet.WIBO/zet.WIBO-szczecin/sbo2025_prod.sql`.
- Raporty: `/Users/michalwozniak/Praca/zet.WIBO/zet.WIBO-szczecin/raporty_sbo`.
- SQL pomocniczy: `/Users/michalwozniak/Praca/zet.WIBO/zet.WIBO-szczecin/db`.

## Zasada migracji

Nie kopiujemy architektury Yii 1:1. Przenosimy reguły biznesowe do domeny Laravel: modele Eloquent, enumy, akcje, serwisy, policy i testy. Filament i widoki publiczne korzystają z domeny, ale nie przechowują krytycznej logiki.

## Aktualny baseline

Utworzono Laravel 13, Filament 5, Spatie Permission, model domenowy, pierwsze migracje, publiczne trasy, podstawowe zasoby Filament oraz testy dla PESEL, statusów, harmonogramu edycji, składania projektu, głosowania i wyników.

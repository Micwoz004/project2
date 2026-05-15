# Głosowanie publiczne

1. Legacy: `VotingController`, `VotingForm`, `VotingTokenForm`, widoki głosowania.
2. Tabele: `voters`, `votecards`, `votes`, `votingtokens`, `newverification`, `smslogs`, `tasks`, `tasktypes`.
3. Dane wejściowe: PESEL, imię, nazwisko, nazwisko matki, telefon/e-mail, token, oświadczenia, wybrane projekty.
4. Dane zapisywane: wyborca, karta, głosy, token, log SMS.
5. Statusy: karta `Accepted=1`, `Rejected=2`, `Verifying=3`.
6. Walidacje: okno głosowania, checksum PESEL, unikalny PESEL, 6-cyfrowy token, limit 5 SMS, zgoda rodzica, hash `newverification`, limity 1 lokalny i 1 ogólnomiejski.
7. Role: anonimowy wyborca publiczny.
8. Edge case: PESEL nie w rejestrze, brak jednej kategorii głosu, token przeterminowany/wyłączony, projekt nie `Picked`.
9. Laravel: `CastVoteService`, `VotingTokenService`, `PeselService`, `VoterHashService`, docelowo Livewire flow.
10. Zgodność: testy transakcji, duplikatu PESEL, tokenów, rejestru hashy, zgody rodzica, braku PESEL i porównanie podsumowania.

## Plan wdrożenia

Status: baseline domenowy rozbudowany.

1. Dodać Livewire flow wyborcy, tokenu i wyboru projektów.
2. Podpiąć `VotingTokenService` pod SMS provider lub adapter testowy.
3. [x] Dodać weryfikację `newverification` i oświadczeń: hash legacy z fallbackiem nazwiska matki `BRAK DANYCH` i pustym stringiem, albo `CitizenConfirmation`.
4. [x] Wymusić potwierdzenie braku jednej kategorii głosu przez `confirm_missing_category`.
5. [x] Pokryć testami token, duplikat PESEL, limity i transakcję.
6. [x] Obsłużyć brak PESEL: karta trafia do statusu `Verifying`, PESEL nie jest zapisywany.
7. [x] Obsłużyć zgodę rodzica/opiekuna dla wyborcy niepełnoletniego.
8. [x] Zachować limit SMS: 6 cyfr, maksymalnie 5 kodów na telefon, unieważnienie poprzednich kodów dla PESEL.
9. [x] Aktywować SMS po `phone + token + disabled=false` zgodnie z legacy.
10. [x] Unieważnić aktywny token SMS po skutecznym oddaniu głosu.
11. [x] Rejestrować papierowe karty głosowania z tą samą walidacją projektów i wyborcy.
12. [x] Importować fixture `newverification`, `votingtokens` i `smslogs`.

## Implementacja Laravel

- `LegacyFixtureImportService` przenosi `newverification`, `votingtokens` i `smslogs`, zachowując hash rejestru, tokeny SMS, flagę `disabled`, typ tokenu, dane zgód w `extra_data` oraz relację logu SMS do wyborcy.
- Jeżeli historyczny `votingtokens.type` jest pusty albo spoza enumu, importer traktuje rekord jako SMS, bo dump 2025 używa tej tabeli dla tokenów SMS.
- Import fixture `voters` przenosi pełniejsze dane wyborcy z legacy: drugie imię, nazwisko matki, ojca, e-mail, adres, IP, user agent, telefon i datę utworzenia.

## Świadome braki na tym etapie

- Brak gotowego Livewire flow i ekranów publicznych dla tokenu SMS.
- Brak integracji z realnym operatorem SMS; `VotingTokenService` przygotowuje rekord domenowy.
- Walidacja tokenu jest domenowo gotowa, ale nie jest jeszcze podpięta do pełnego UI formularza głosowania.

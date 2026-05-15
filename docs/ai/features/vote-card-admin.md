# Administracja kartami głosowania

1. Legacy: `VoteCardController`, `VoteController`, `VoterController`, `VoteCardForm`.
2. Tabele: `votecards`, `votes`, `voters`, `tasks`.
3. Dane wejściowe: karta papierowa/elektroniczna, status, notatki, checkout, głosy.
4. Dane zapisywane: status karty, przypisane głosy, użytkownik obsługujący, notatki.
5. Statusy: accepted, rejected, verifying.
6. Walidacje: liczba głosów, poprawność projektów, status karty, unikalność głosów na karcie.
7. Role: administratorzy głosowania, konsultanci, BDO.
8. Edge case: ręczna karta papierowa, karta weryfikowana, odrzucenie po sprawdzeniu, duplikat.
9. Laravel: `RegisterPaperVoteCardAction`, `UpdateVoteCardStatusAction`, `VoteCardPolicy`, docelowo Filament Resource dla `VoteCard`.
10. Zgodność: testy statusów i liczenia wyników po zmianie statusu.

## Plan wdrożenia

Status: baseline domenowy i administracyjny UI rozpoczęte.

1. [x] Dodać Filament Resource dla kart głosowania.
2. [x] Dodać akcje zmiany statusu z logowaniem.
3. [x] Dodać obsługę kart papierowych w domenie.
4. [x] Zablokować niedozwolone kombinacje projektów i głosów przez domenową walidację `CastVoteService`.
5. [x] Pokryć testami statusy i wpływ na wyniki.
6. [x] Dodać akcję Filament do rejestracji papierowej karty głosowania.
7. [x] Dodać akcję Filament do edycji głosów istniejącej karty.

## Implementacja Laravel

- `VoteCardPolicy` dopuszcza podgląd i zmianę kart tylko dla `vote_cards.manage` albo ról `admin`/`bdo`.
- `RegisterPaperVoteCardAction` rejestruje papierową kartę przez operatora z `vote_cards.manage`/`voting.manage`, nadaje kolejny `current_paper_card_no`, ustawia `digital=false` i zapisuje `created_by_id`.
- `UpdateVoteCardStatusAction` zmienia status karty na `Accepted`, `Rejected` albo `Verifying`, zapisuje operatora w `checkout_user_id`, czas w `checkout_date_time` i notatkę administracyjną.
- `VoteCardResource` daje administracyjny podgląd kart w Filament oraz edycję statusu i notatek z dostępem przez `VoteCardPolicy`; edycja używa `UpdateVoteCardStatusAction`, więc zapisuje operatora i czas checkoutu.
- `ListVoteCards` ma akcję nagłówka rejestrującą papierową kartę przez `RegisterPaperVoteCardAction`; formularz zbiera dane wyborcy, projekt lokalny/ogólnomiejski, oświadczenie i zgody, a walidacja limitów oraz statusów projektów pozostaje w domenie.
- `EditVoteCard` ma akcję zmiany głosów istniejącej karty. `ReplaceVoteCardVotesAction` usuwa stare głosy, zapisuje nowe, pilnuje limitu po jednej kategorii, statusu `Picked`, typu obszaru oraz wymagania potwierdzenia przy pominiętej kategorii.
- `LegacyFixtureImportService` przenosi pełniejsze pola `votecards`: zgody, operatorów, konsultanta, checkout, adresy oświadczeń, rodzica/opiekuna, IP oraz timestamps.
- `ResultsCalculator` liczy tylko karty `Accepted`, więc akceptacja/odrzucenie po ręcznej weryfikacji natychmiast zmienia wynik zgodnie z legacy.

## Świadome braki na tym etapie

- UI edycji głosów ma obecnie formularz administracyjny dla wskazania nowych projektów; porównanie finalnych komunikatów i etykiet z legacy pozostaje do decyzji wdrożeniowej.

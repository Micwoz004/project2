# Administracja kartami głosowania

1. Legacy: `VoteCardController`, `VoteController`, `VoterController`, `VoteCardForm`.
2. Tabele: `votecards`, `votes`, `voters`, `tasks`.
3. Dane wejściowe: karta papierowa/elektroniczna, status, notatki, checkout, głosy.
4. Dane zapisywane: status karty, przypisane głosy, użytkownik obsługujący, notatki.
5. Statusy: accepted, rejected, verifying.
6. Walidacje: liczba głosów, poprawność projektów, status karty, unikalność głosów na karcie.
7. Role: administratorzy głosowania, konsultanci, BDO.
8. Edge case: ręczna karta papierowa, karta weryfikowana, odrzucenie po sprawdzeniu, duplikat.
9. Laravel: `UpdateVoteCardStatusAction`, `VoteCardPolicy`, docelowo Filament Resource dla `VoteCard`.
10. Zgodność: testy statusów i liczenia wyników po zmianie statusu.

## Plan wdrożenia

Status: baseline domenowy rozpoczęty.

1. Dodać Filament Resource dla kart i głosów.
2. [x] Dodać akcje zmiany statusu z logowaniem.
3. Dodać obsługę kart papierowych.
4. Zablokować niedozwolone kombinacje projektów i głosów.
5. [x] Pokryć testami statusy i wpływ na wyniki.

## Implementacja Laravel

- `VoteCardPolicy` dopuszcza podgląd i zmianę kart tylko dla `vote_cards.manage` albo ról `admin`/`bdo`.
- `UpdateVoteCardStatusAction` zmienia status karty na `Accepted`, `Rejected` albo `Verifying`, zapisuje operatora w `checkout_user_id`, czas w `checkout_date_time` i notatkę administracyjną.
- `ResultsCalculator` liczy tylko karty `Accepted`, więc akceptacja/odrzucenie po ręcznej weryfikacji natychmiast zmienia wynik zgodnie z legacy.

## Świadome braki na tym etapie

- Brak Filament Resource dla ręcznej obsługi kart.
- Brak pełnego formularza kart papierowych i walidacji ręcznych wpisów.
- Brak podpięcia policy do konkretnych Filament Actions, bo Resource kart nie jest jeszcze gotowy.

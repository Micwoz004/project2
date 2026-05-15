# Procesy biznesowe

## Edycja SBO

Stan procesu jest liczony z dat edycji tak jak `TaskGroup::getState`: inactive, składanie, weryfikacja przed głosowaniem, korekty, głosowanie, weryfikacja wyników, publikacja wyników.

Walidacja harmonogramu zachowuje legacy `TaskGroup::afterValidate`: wymagane daty, brak wcześniejszego końca niż start dla sprawdzanych par etapów oraz blokada zapisu, gdy inna edycja ma `resultAnnouncementEnd > proposeStart`. Po utworzeniu edycji system zakłada strony treści dla symboli legacy `V,S,A,I,TY,W,T`.

## Projekt

1. Autor tworzy kopię roboczą.
2. Formularz wymaga danych opisowych, lokalizacji, obszaru, kosztów, zgód i listy poparcia.
3. Przy złożeniu projekt przechodzi na `Submitted`.
4. System blokuje zwykłą edycję po złożeniu, chyba że projekt jest w korekcie.
5. Każdy zapis istotny dla procesu tworzy wersję.
6. Administracja wykonuje weryfikację formalną i merytoryczną.
7. Projekty z `Picked` trafiają na listę głosowania.

## Publiczny katalog projektów

Katalog pokazuje wyłącznie projekty widoczne publicznie według policy, filtruje po edycji, obszarze, kategorii i wyszukiwaniu oraz zachowuje kolejność legacy po numerze losowania `numberDrawn`, następnie numerze projektu.

## Weryfikacje

Proces legacy rozdziela ocenę formalną, wstępną merytoryczną, konsultacje departamentów, końcową merytoryczną oraz głosowania rad/komisji. Nowy model zachowuje osobne tabele etapów i wspólny model przydziałów.

## Głosowanie publiczne

1. Głosowanie działa tylko w oknie `voting_start` - `voting_end`.
2. Wyborca podaje dane identyfikacyjne i PESEL.
3. PESEL ma checksum, datę urodzenia i wymóg zgody rodzica dla osób poniżej 18 lat.
4. System sprawdza hash w `newverification` albo wymaga oświadczenia.
5. Token SMS ma 6 cyfr, limit 5 SMS na telefon i unieważnia stare tokeny PESEL.
6. Jeden PESEL może zagłosować raz w edycji.
7. Limit legacy: 1 głos lokalny i 1 głos ogólnomiejski.
8. Do głosowania trafiają tylko projekty `ProjectStatus::Picked`.
9. Głosy zapisują się transakcyjnie jako karta i rekordy `votes`.

## Wyniki

Wyniki sumują `votes.points` tylko dla kart `VoteCardStatus::Accepted`. Raporty administracyjne mogą używać szerszego zakresu danych niż widok publiczny, dlatego różnice muszą być jawnie dokumentowane przed odtworzeniem każdego raportu.

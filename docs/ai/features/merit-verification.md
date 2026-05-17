# Ocena merytoryczna

1. Legacy: `TaskInitialMeritVerification`, `TaskFinishMeritVerification`, `MeritVerificationController`.
2. Tabele: `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation`, `tasksdepartments`, `tasks`.
3. Dane wejściowe: odpowiedzi merytoryczne, opinie departamentów, konsultacje, koszt, lokalizacja, rekomendacje.
4. Dane zapisywane: wyniki, komentarze, przydziały, status projektu.
5. Statusy: `DuringInitialVerification=11`, `SentForMeritVerification=12`, `DuringMeritVerification=13`, `MeritVerificationAccepted=14`, `MeritVerificationRejected=-12`.
6. Walidacje: kompletność odpowiedzi, przypisanie departamentu, wymagane uzasadnienia negatywne.
7. Role: weryfikatorzy, departamenty, koordynatorzy, BDO.
8. Edge case: wiele departamentów, zwrot do uzupełnienia, zmiana kosztu, kolizja z korektą.
9. Laravel: `initial_merit_verifications`, `final_merit_verifications`, `consultation_verifications`.
10. Zgodność: testy formularzy i przejść statusów per legacy.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 3.

1. [x] Spisać podstawową logikę wstępnej, końcowej i konsultacyjnej oceny merytorycznej.
2. [x] Dodać przydziały departamentów i terminy.
3. [x] Dodać akcje zakończenia oceny z decyzją.
4. [x] Udostępnić bazowe formularze w Filament według roli/departamentu.
5. [x] Pokryć testami przydziały, konsultacje, koszty i negatywne wyniki.
6. [x] Uzupełnić formularze pól legacy dla kart wstępnych i końcowych w UI/DTO.
7. [x] Dodać import fixture dla `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation` i `taskdepartmentassignment`.
8. [x] Dodać import fixture dla `detailedverification`, `locationverification` i `verificationversion`.
9. [x] Dodać import fixture dla `coordinatorassignment` i `verifierassignment`.
10. [x] Dodać import fixture dla `taskadvancedverification`.
11. [x] Dodać import fixture dla `prerecommendations` i `recommendationswjo`.
12. [x] Dodać import fixture dla `tasksinitialverification` i `tasksdepartments`.
13. [x] Dodać import fixture dla monitów `verificationpressure`.

## Rozpoznane reguły legacy

- `TaskDepartmentAssignment` ma typy: `TYPE_MERIT_INITIAL=1`, `TYPE_MERIT_FINISH=2`, `TYPE_CONSULTATION=3`, `TYPE_FORMAL_VERIFICATION=4`.
- `TaskInitialMeritVerification`, `TaskFinishMeritVerification` i `TaskConsultation` mają statusy kart: `STATUS_WORKING_COPY=1` oraz `STATUS_SENT=2`.
- Wysłanie karty ustawia `sendDate` i oznacza przydział jako zwrócony z wynikiem (`isReturned=0`, `sendDate=NOW()`).
- Karty weryfikacji są wersjonowane przez `VerificationVersion`; importer zachowuje historyczne snapshoty, a tworzenie nowych snapshotów w akcjach Laravel zostaje do dopisania.
- Końcowa weryfikacja zapisuje `correctedCostJson` i `futureCostJson`; przy wysłaniu każde pole kosztu musi mieć opis i sumę. Jeśli `hasAdditionalCosts` ma wartość `NO` albo `N/A`, legacy `parseFutureCosts()` pomijało koszty przyszłe i Laravel robi to samo.
- Konsultacja zapisuje wynik i komentarze, ale nie zmienia bezpośrednio statusu projektu w kontrolerze karty.

## Zaimplementowany odpowiednik Laravel

- `VerificationAssignment` odwzorowuje `taskdepartmentassignment`.
- `VerificationCardStatus` odwzorowuje statusy kart legacy.
- `InitialMeritVerification`, `FinalMeritVerification`, `ConsultationVerification` zapisują karty w istniejących tabelach domenowych.
- `RecordVerificationVersionAction` zapisuje snapshot każdej nowej karty wstępnej, końcowej i konsultacyjnej do `verification_versions` z typem zgodnym z `TaskDepartmentAssignment`.
- `AssignVerificationDepartmentAction` tworzy lub aktualizuje przydział departamentu dla konkretnego typu weryfikacji.
- `SubmitInitialMeritVerificationAction` wymaga przydziału przy wysyłce, zapisuje kartę i przenosi projekt do `SentForMeritVerification` albo `InitialVerificationRejected`.
- `SubmitFinalMeritVerificationAction` wymaga przydziału przy wysyłce, waliduje koszty i przenosi projekt do `MeritVerificationAccepted` albo `MeritVerificationRejected`.
- Przy wielu przydziałach departamentów status projektu pozostaje odpowiednio `DuringInitialVerification` albo `DuringMeritVerification` do czasu odesłania wszystkich kart danego typu; negatywna karta po zakończeniu kompletu przydziałów rozstrzyga status negatywny.
- `SubmitConsultationVerificationAction` wymaga przydziału przy wysyłce i oznacza konsultację jako wysłaną bez zmiany statusu projektu.
- `LegacyFixtureImportService` importuje historyczne karty weryfikacji, konsultacje i przydziały, zapisując pełny rekord w `raw_legacy_payload`.
- `DetailedVerification` i `LocationVerification` zachowują specyficzne formularze legacy w `answers` JSON oraz osobne pola wyniku, rekomendacji, publiczności i dat.
- `ProjectUserAssignment` konsoliduje legacy `coordinatorassignment` i `verifierassignment` w jedną tabelę z rolą przypisania.
- `AdvancedVerification` zachowuje historyczny formularz `taskadvancedverification` w pełnym payloadzie JSON oraz pola procesu: projekt, jednostkę, operatora, status i datę wysłania.
- `ProjectDepartmentRecommendation` konsoliduje legacy `prerecommendations` i `recommendationswjo`, zachowując opinię, notatki, koszt, datę wysłania i odpowiedzi formularza WJO.
- `ProjectDepartmentScope` konsoliduje legacy `tasksinitialverification` i `tasksdepartments`, odtwarzając listę jednostek uprawnionych do opiniowania projektu.
- `VerificationPressureLog` zachowuje legacy `verificationpressure`: tytuł/treść monitu, odbiorców, typ (`2` dyrektor, `3` ręczny), jednostkę i legacy ID przydziału.
- `ProjectResource` w Filament udostępnia przydzielanie jednostek do typów `MeritInitial`, `MeritFinish` i `Consultation` oraz wysyłkę kart wstępnych, końcowych i konsultacyjnych. Widoczność akcji wymaga uprawnień weryfikacyjnych i statusów pasujących do etapu.
- Formularz wstępnej oceny merytorycznej w Filament zapisuje klucze legacy z `TaskInitialMeritVerification`: bloki BDO, Prezydenta, środowiska, zarządzania projektami, majątku, mieszkalnictwa, urbanistyki i zabytków wraz z komentarzami oraz polami tekstowymi rekomendacji/właściciela/informacji.
- Formularz końcowej oceny merytorycznej w Filament zapisuje klucze legacy z `TaskFinishMeritVerification`: pytania prawne, dostępnościowe, wykonalnościowe, budżetowe, kosztów przyszłych, gospodarności, dostępności/nieodpłatności, modyfikacji i opinii jednostek wraz z komentarzami oraz dodatkowymi informacjami.
- Bazowe formularze UI zapisują wynik, treść opinii, uzasadnienie negatywne oraz wielowierszowe pozycje kosztu szacunkowego/przyszłego zgodne z legacy `correctedCostJson` i `futureCostJson`.
- `VerificationOverviewService` buduje administracyjny podgląd przydziałów, kart i wersji `verification_versions`; `ProjectResource` pokazuje go jako modal `Historia weryfikacji` dla użytkowników z uprawnieniami weryfikacyjnymi.

## Świadome uproszczenia na tym etapie

- Konsultacje nadal mają prosty formularz opinii/wyniku; pełne specjalistyczne ekrany konsultacyjne można rozbudować, jeśli w legacy widok konkretnej jednostki wymaga osobnych pól poza `TaskConsultation`.
- Układ wielowierszowego kosztorysu w Filament jest technicznie inny niż legacy Yii, ale zachowuje te same dane: opis i sumę każdej składowej.
- Podgląd historii weryfikacji jest tekstowym modalem administracyjnym; docelowo można go rozbudować do tabel zależnych, jeśli będzie potrzebny pełny ekran analityczny.

## Zgodność do sprawdzenia

- Porównać pełne payloady `taskinitialmeritverification`, `taskfinishmeritverification`, `taskconsultation` w imporcie legacy z wartościami zapisywanymi z nowych formularzy.
- Dopisać wersjonowanie kart i logikę zwrotu do kopii roboczej (`setAsReturned`).
- Uzupełnić reguły przejść statusów na poziomie koordynatora, gdy wiele departamentów ma przydziały równolegle.

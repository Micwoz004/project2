# Załączniki i współautorzy

1. Legacy: `File`, `FilePrivate`, `Cocreator`, `Task::rules`, widoki formularzy projektu.
2. Tabele: `files`, `filesprivate`, `cocreators`, `tasks`.
3. Dane wejściowe: pliki projektu, lista poparcia, zgody właściciela, mapa, zgoda rodzica, dane współautorów.
4. Dane zapisywane: metadane plików, prywatność, typ, autor pliku, zgody współautorów.
5. Statusy: zwykle nie zmieniają statusu same, ale są wymagane przed `Submitted`.
6. Walidacje: typy `doc,docx,rtf,xls,txt,jpg,png,bmp,gif,tif,pdf,pptx`, limit legacy, wymagane zgody.
7. Role: autor, administracja, departamenty dla załączników weryfikacji.
8. Edge case: plik prywatny, anonimizacja załączników, duplikaty, brak wymaganej zgody.
9. Laravel: `ProjectFile`, `ProjectCoauthor`, typ `ProjectFileType`, storage prywatny/publiczny.
10. Zgodność: test uploadów, typów, prywatności i widoczności publicznej.

## Plan wdrożenia

Status: częściowo zaimplementowane w etapie 1.

1. [x] Dodać storage publiczny/prywatny i fizyczny upload plików.
2. [x] Rozbudować `ProjectFileType` o pełne typy legacy i limity liczby plików.
3. [x] Dodać domenową synchronizację współautorów i zgód.
4. [x] Ustalić bazową bramkę publikacji załączników po anonimizacji.
5. [x] Pokryć testami metadane plików, typy, limity i wymagane zgody.
6. [x] Dodać import fixture dla `files`, `filesprivate` i `cocreators`.

## Rozpoznane reguły legacy

- `Task::rules` dopuszcza rozszerzenia: `doc`, `docx`, `rtf`, `xls`, `txt`, `jpg`, `png`, `bmp`, `gif`, `tif`, `pdf`, `pptx`.
- Legacy limit rozmiaru jest zapisany jako `1024 * 1024 * 10000`, mimo że komunikat użytkownika mówi o 10 MB. Nowy kod zachowuje stałą z kodu jako źródło prawdy i oznacza różnicę komunikatu do późniejszego uporządkowania.
- Limity liczby załączników z `Task::beforeValidate`: `Other=10`, `Map=5`, `ParentAgreement=5`, `SupportList=5`, `OwnerAgreement=5`.
- Legacy formularz obsługuje maksymalnie dwóch współautorów (`cocreator1`, `cocreator2`).
- `Task::cocreatorValidate` wymaga imienia, nazwiska, e-maila lub telefonu oraz potwierdzenia zgody.
- `Cocreator::validateContact` wymaga wybrania co najmniej jednej publicznej formy kontaktu: e-mail lub telefon.

## Zaimplementowany odpowiednik Laravel

- `ProjectFileType` zawiera typy legacy i metody `legacyAllowedExtensions()` oraz `maxFiles()`.
- `ProjectFileValidator` sprawdza rozszerzenie, rozmiar i limit liczby plików danego typu.
- `RegisterProjectFileAction` rejestruje metadane pliku i loguje operację bez treści pliku oraz bez PII.
- `StoreProjectFileAction` zapisuje fizyczny plik na dysku `public` albo `local`, a następnie tworzy `ProjectFile`; walidacja legacy jest wykonywana przed zapisem do storage.
- `MarkProjectAttachmentsAnonymizedAction` oznacza projekt jako gotowy do publicznej prezentacji załączników.
- `ProjectFile::publiclyVisible()` udostępnia publicznie wyłącznie pliki `is_private=false` oraz tylko wtedy, gdy projekt ma `attachments_anonymized=true`.
- Publiczny formularz zgłoszenia projektu zapisuje plik listy poparcia w prywatnym storage i oznacza go jako załącznik formularza.
- Publiczny formularz obsługuje dodatkowe uploady legacy: zgody właściciela i rodzica jako prywatne, załączniki mapy oraz pozostałe załączniki jako publiczne dopiero po anonimizacji projektu.
- Publiczny formularz korekty autora zapisuje tylko te typy załączników, które zostały odblokowane w `project_corrections.allowed_fields`.
- `ProjectCoauthorValidator` waliduje limit dwóch współautorów, wymagane dane kontaktowe, potwierdzenie przeczytania i zgodę na co najmniej jedną formę kontaktu.
- `SyncProjectCoauthorsAction` wymienia listę współautorów projektu transakcyjnie.
- Publiczny formularz zgłoszenia projektu przyjmuje maksymalnie dwóch współautorów, pomija puste sloty formularza i zapisuje dane przez `SyncProjectCoauthorsAction` przed złożeniem projektu.
- `LegacyFixtureImportService` rozdziela `files` i `filesprivate` flagą `is_private` oraz przenosi współautorów z `cocreators`.

## Zgodność do sprawdzenia

- Potwierdzić, czy historyczny limit `1024 * 1024 * 10000` ma zostać zachowany, czy świadomie skorygowany do 10 MB w UI i walidacji.
- Doprecyzować, czy legacy wykonywało fizyczną transformację plików podczas anonimizacji; aktualny baseline traktuje anonimizację jako decyzję publikacyjną po ręcznym przygotowaniu pliku.

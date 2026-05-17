<?php

namespace App\Domain\Projects\Support;

final class LegacyProjectFormText
{
    /**
     * @return array<string, array{legacy: string, label: string}>
     */
    public static function formalAnswerFields(): array
    {
        return [
            'was_sent_on_correct_form' => [
                'legacy' => 'wasSentOnCorrectForm',
                'label' => 'Czy projekt został złożony na właściwym formularzu?',
            ],
            'was_sent_in_time' => [
                'legacy' => 'wasSentInTime',
                'label' => 'Czy projekt przesłano we właściwym terminie?',
            ],
            'was_sent_in_compliance_with_rules' => [
                'legacy' => 'wasSentInComplianceWithRules',
                'label' => 'Czy projekt został złożony do Urzędu zgodnie z obowiązującymi zasadami SBO?',
            ],
            'has_leader_contact_data' => [
                'legacy' => 'hasLeaderContactData',
                'label' => 'Czy projekt zawiera dane kontaktowe do autora  i współautorów (imię i nazwisko, numer telefonu, adres e-mail oraz miejsce zamieszkania)?',
            ],
            'has_proper_attachments' => [
                'legacy' => 'hasProperAttachments',
                'label' => 'Czy załączono niezbędne załączniki? Czy zostały one zanonimizowane?',
            ],
            'has_support_attachment' => [
                'legacy' => 'hasSupportAttachment',
                'label' => 'Czy załączona została lista poparcia zawierająca podpisy minimum 10 osób popierających projekt, z wyłączeniem autora projektu?',
            ],
            'is_data_correct' => [
                'legacy' => 'isDataCorrect',
                'label' => 'Czy projekt został wypełniony prawidłowo? Czy wypełniono w czytelny sposób wszystkie pola oznaczone jako obowiązkowe?',
            ],
            'is_description_valid' => [
                'legacy' => 'isDescriptionValid',
                'label' => 'Czy opis projektu nie budzi wątpliwości pod względem jasności, konkretności, jednoznaczności opisu?',
            ],
            'is_free_of_charge' => [
                'legacy' => 'isFreeOfCharge',
                'label' => 'Czy autor projektu zawarł informacje o ogólnodostępności i nieodpłatności projektu?',
            ],
            'is_correctly_assigned' => [
                'legacy' => 'isCorrectlyAssigned',
                'label' => 'Czy projekt został prawidłowo przyporządkowany do odpowiedniej kategorii i obszaru lokalnego?',
            ],
            'is_map_correct' => [
                'legacy' => 'isMapCorrect',
                'label' => 'Czy autor prawidłowo wskazał lokalizację projektu, w szczególności czy wskazał numer działki i obręb?',
            ],
            'has_required_consent' => [
                'legacy' => 'hasRequiredConsent',
                'label' => 'Czy w formularzu projektu zostały prawidłowo złożone wszystkie wymagane oświadczenia?',
            ],
            'is_description_fair' => [
                'legacy' => 'isDescriptionFair',
                'label' => 'Czy opis projektu nie zawiera wskazania potencjalnego wykonawcy lub dostawcy?',
            ],
            'is_in_budget' => [
                'legacy' => 'isInBudget',
                'label' => 'Czy wartość projektu określona przez autora mieści się w puli środków SBO przeznaczonych na projekty z danej kategorii i danego obszaru lokalnego?',
            ],
            'is_located_within_city' => [
                'legacy' => 'isLocatedWithinCity',
                'label' => 'Czy projekt jest zlokalizowany wyłącznie w granicach administracyjnych Miasta?',
            ],
            'is_in_own_tasks' => [
                'legacy' => 'isInOwnTasks',
                'label' => 'Czy projekt mieści się w zadaniach własnych Gminy?',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function publicSubmissionStatements(): array
    {
        return [
            'contact_publication_hint' => 'Proszę wskazać poniżej, która forma kontaktu i wybrany kontakt (odpowiednio numer telefonu albo adres e-mail) będzie publikowana na stronie internetowej sbo.szczecin.eu, w Biuletynie Informacji Publicznej Urzędu Miasta Szczecin (konsultuj.szczecin.pl lub bip.um.szczecin.pl/konsultacje), zgodnie z Regulaminem SBO (§ 12. ust. 13):',
            'evaluation_consent_lead' => 'Na podstawie z art. 6 ust. 1 lit a rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. w sprawie ochrony osób fizycznych w związku z przetwarzaniem danych osobowych i w sprawie swobodnego przepływu takich danych oraz uchylenia dyrektywy 95/46/WE (ogólne rozporządzenie o ochronie danych) (Dz. U. UE. L. z 2016 r. Nr 119, str. 1 z późn. zm.) oświadczam, że wyrażam zgodę na przetwarzanie moich danych osobowych przez Gminę Miasto Szczecin - Urząd Miasta Szczecin w celu:',
            'evaluation_consent_checkbox' => 'przeprowadzenia ewaluacji konsultacji społecznych dotyczących Szczecińskiego Budżetu Obywatelskiego 2025.',
            'evaluation_consent_note' => '* Wyrażenie zgody jest dobrowolne i może być w dowolnym momencie wycofane poprzez kontakt za pomocą poczty e-mail z Biurem Dialogu Obywatelskiego bdo@um.szczecin.pl. Wycofanie zgody nie ma wpływu na zgodność przetwarzania, którego dokonano na podstawie zgody przed jej wycofaniem.',
            'regulation_confirmation' => 'zapoznałem się z Regulaminem przeprowadzania konsultacji społecznych dotyczących Szczecińskiego Budżetu Obywatelskiego.',
            'attachments_anonymized' => 'posiadam prawa pozwalające na udostępnienie załączników osobom trzecim poprzez publikację w systemie teleinformatycznym SBO, a ich publikacja nie będzie naruszała praw osób trzecich, w tym m.in. autorskich praw majątkowych i osobistych do utworu oraz prawa do wizerunku. Jestem właścicielem materiałów graficznych dołączonych do złożonego przeze mnie projektu SBO 2025 lub przysługują mi prawa autorskie majątkowe do materiałów graficznych dołączonych do złożonego przeze mnie projektu SBO 2025 i wyrażam zgodę na ich opublikowanie.',
            'consent_to_change' => 'Wyrażam zgodę na wprowadzanie we wniosku zmian przez Urząd Miasta Szczecin w porozumieniu z Liderem/Liderką',
            'support_list' => 'Potwierdzam dołączenie listy poparcia zawierającej podpisy wymaganej liczby mieszkańców popierających projekt i niebędących autorami wniosku.',
        ];
    }
}

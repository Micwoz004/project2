<?php

namespace App\Products\CivicBudget\Filament\Resources\Projects;

use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\Projects\Actions\ApplyCorrectionAction;
use App\Products\CivicBudget\Domain\Projects\Actions\StartCorrectionAction;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectCorrectionField;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Category;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectCorrection;
use App\Products\CivicBudget\Domain\Projects\Support\LegacyProjectFormText;
use App\Platform\Users\Enums\SystemPermission;
use App\Platform\Users\Models\Department;
use App\Products\CivicBudget\Domain\Verification\Actions\AssignVerificationDepartmentAction;
use App\Products\CivicBudget\Domain\Verification\Actions\BeginFormalVerificationAction;
use App\Products\CivicBudget\Domain\Verification\Actions\CastProjectBoardVoteAction;
use App\Products\CivicBudget\Domain\Verification\Actions\CloseBoardVotingAction;
use App\Products\CivicBudget\Domain\Verification\Actions\CompleteFormalVerificationAction;
use App\Products\CivicBudget\Domain\Verification\Actions\DecideProjectAppealAction;
use App\Products\CivicBudget\Domain\Verification\Actions\ForwardFormalVerificationToInitialVerificationAction;
use App\Products\CivicBudget\Domain\Verification\Actions\RequestFormalCorrectionAction;
use App\Products\CivicBudget\Domain\Verification\Actions\RespondProjectAppealAction;
use App\Products\CivicBudget\Domain\Verification\Actions\RestartBoardVotingAction;
use App\Products\CivicBudget\Domain\Verification\Actions\ReturnVerificationCardAction;
use App\Products\CivicBudget\Domain\Verification\Actions\SubmitConsultationVerificationAction;
use App\Products\CivicBudget\Domain\Verification\Actions\SubmitFinalMeritVerificationAction;
use App\Products\CivicBudget\Domain\Verification\Actions\SubmitInitialMeritVerificationAction;
use App\Products\CivicBudget\Domain\Verification\Actions\SubmitProjectAppealAction;
use App\Products\CivicBudget\Domain\Verification\Enums\AtVoteChoice;
use App\Products\CivicBudget\Domain\Verification\Enums\BoardType;
use App\Products\CivicBudget\Domain\Verification\Enums\OtVoteChoice;
use App\Products\CivicBudget\Domain\Verification\Enums\ProjectAppealFirstDecision;
use App\Products\CivicBudget\Domain\Verification\Enums\VerificationAssignmentType;
use App\Products\CivicBudget\Domain\Verification\Enums\VerificationCardStatus;
use App\Products\CivicBudget\Domain\Verification\Enums\ZkVoteChoice;
use App\Products\CivicBudget\Domain\Verification\Models\ConsultationVerification;
use App\Products\CivicBudget\Domain\Verification\Models\FinalMeritVerification;
use App\Products\CivicBudget\Domain\Verification\Models\FormalVerification;
use App\Products\CivicBudget\Domain\Verification\Models\InitialMeritVerification;
use App\Products\CivicBudget\Domain\Verification\Models\ProjectAppeal;
use App\Products\CivicBudget\Domain\Verification\Models\VerificationAssignment;
use App\Products\CivicBudget\Domain\Verification\Models\VerificationVersion;
use App\Products\CivicBudget\Domain\Verification\Services\VerificationOverviewService;
use App\Products\CivicBudget\Filament\Resources\Projects\Pages\CreateProject;
use App\Products\CivicBudget\Filament\Resources\Projects\Pages\EditProject;
use App\Products\CivicBudget\Filament\Resources\Projects\Pages\ListProjects;
use App\Products\CivicBudget\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * @var array<int, string>
     */
    private const MERIT_VALUE_OPTIONS = [
        0 => 'Nie',
        1 => 'Tak',
        2 => 'Nie dotyczy',
    ];

    /**
     * @var array<string, array{legacy: string, label: string, comments?: bool}>
     */
    private const INITIAL_MERIT_ANSWER_FIELDS = [
        'citizen_dialog_office_question_1' => [
            'legacy' => 'citizenDialogOfficeQuestion1',
            'label' => 'BDO: projekt polega wyłącznie na sporządzeniu projektu, planu albo dokumentacji?',
        ],
        'citizen_dialog_office_question_2' => [
            'legacy' => 'citizenDialogOfficeQuestion2',
            'label' => 'BDO: charakter projektu',
        ],
        'citizen_dialog_office_result' => [
            'legacy' => 'citizenDialogOfficeResult',
            'label' => 'BDO: czy projekt może przejść do następnego etapu?',
        ],
        'mayor_office_question_1' => [
            'legacy' => 'mayorOfficeQuestion1',
            'label' => 'Prezydent: zgodność ze Strategią Rozwoju Szczecina',
        ],
        'mayor_office_question_2' => [
            'legacy' => 'mayorOfficeQuestion2',
            'label' => 'Prezydent: mieści się w zadaniach własnych Gminy',
        ],
        'mayor_office_result' => [
            'legacy' => 'mayorOfficeResult',
            'label' => 'Prezydent: czy projekt może przejść do następnego etapu?',
        ],
        'environment_office_question_1' => [
            'legacy' => 'environmentOfficeQuestion1',
            'label' => 'Środowisko: projekt spełnia kryteria Zielonego SBO',
        ],
        'environment_office_question_2' => [
            'legacy' => 'environmentOfficeQuestion2',
            'label' => 'Środowisko: zakłada realizację innych celów niż wskazane',
        ],
        'environment_office_question_3' => [
            'legacy' => 'environmentOfficeQuestion3',
            'label' => 'Środowisko: możliwy na terenie/obiekcie objętym ochroną przyrody',
        ],
        'environment_office_question_4' => [
            'legacy' => 'environmentOfficeQuestion4',
            'label' => 'Środowisko: możliwe wyłączenie gruntu z produkcji rolnej',
        ],
        'environment_office_result' => [
            'legacy' => 'environmentOfficeResult',
            'label' => 'Środowisko: kwalifikuje się do Zielonego SBO i realizacji',
        ],
        'project_management_office_question_1' => [
            'legacy' => 'projectManagementOfficeQuestion1',
            'label' => 'Zarządzanie projektami: zgodność z Wieloletnim Programem Rozwoju',
        ],
        'project_management_office_question_2' => [
            'legacy' => 'projectManagementOfficeQuestion2',
            'label' => 'Zarządzanie projektami: inwestycja jest już w budżecie lub planach',
        ],
        'project_management_office_question_3' => [
            'legacy' => 'projectManagementOfficeQuestion3',
            'label' => 'Zarządzanie projektami: podobne działanie było już realizowane',
        ],
        'project_management_office_question_4' => [
            'legacy' => 'projectManagementOfficeQuestion4',
            'label' => 'Zarządzanie projektami: zaplanowano analogiczne zadania',
        ],
        'project_management_office_question_5' => [
            'legacy' => 'projectManagementOfficeQuestion5',
            'label' => 'Zarządzanie projektami: projekt koliduje z innymi działaniami',
        ],
        'project_management_office_result' => [
            'legacy' => 'projectManagementOfficeResult',
            'label' => 'Zarządzanie projektami: projekt mógłby zostać zrealizowany',
        ],
        'property_office_suboffice1_property_owner_skip' => [
            'legacy' => 'propertyOfficeSuboffice1PropertyOwnerSkip',
            'label' => 'Majątek 1: pominięto wskazanie właściciela działki',
            'comments' => false,
        ],
        'property_office_suboffice1_question_1' => [
            'legacy' => 'propertyOfficeSuboffice1Question1',
            'label' => 'Majątek 1: teren przeznaczony do zbycia w drodze zamiany',
        ],
        'property_office_suboffice1_result' => [
            'legacy' => 'propertyOfficeSuboffice1Result',
            'label' => 'Majątek 1: czy projekt może przejść do następnego etapu?',
        ],
        'property_office_suboffice2_question_1' => [
            'legacy' => 'propertyOfficeSuboffice2Question1',
            'label' => 'Majątek 2: miejsce przeznaczone na sprzedaż lub w procedurze zbycia',
        ],
        'property_office_suboffice2_question_2' => [
            'legacy' => 'propertyOfficeSuboffice2Question2',
            'label' => 'Majątek 2: teren inwestycyjny albo rezerwa na inny cel',
        ],
        'property_office_suboffice2_question_3' => [
            'legacy' => 'propertyOfficeSuboffice2Question3',
            'label' => 'Majątek 2: możliwa realizacja po wydzieleniu części działki',
        ],
        'property_office_suboffice2_result' => [
            'legacy' => 'propertyOfficeSuboffice2Result',
            'label' => 'Majątek 2: czy projekt może przejść do następnego etapu?',
        ],
        'housing_office_question_1' => [
            'legacy' => 'housingOfficeQuestion1',
            'label' => 'Mieszkalnictwo: nieruchomość obciążona na rzecz osób trzecich',
        ],
        'housing_office_question_2' => [
            'legacy' => 'housingOfficeQuestion2',
            'label' => 'Mieszkalnictwo: nieruchomość przeznaczona do obciążenia',
        ],
        'housing_office_question_3' => [
            'legacy' => 'housingOfficeQuestion3',
            'label' => 'Mieszkalnictwo: realizacja może naruszać prawa osób trzecich',
        ],
        'housing_office_question_4' => [
            'legacy' => 'housingOfficeQuestion4',
            'label' => 'Mieszkalnictwo: przedstawiono właściwe oświadczenie właściciela',
        ],
        'housing_office_question_5' => [
            'legacy' => 'housingOfficeQuestion5',
            'label' => 'Mieszkalnictwo: przedstawiono zgodę instytucji',
        ],
        'housing_office_question_6' => [
            'legacy' => 'housingOfficeQuestion6',
            'label' => 'Mieszkalnictwo: teren objęty procedurą sprzedaży lokalu i gruntu',
        ],
        'housing_office_result' => [
            'legacy' => 'housingOfficeResult',
            'label' => 'Mieszkalnictwo: czy projekt może przejść do następnego etapu?',
        ],
        'urban_office_question_1' => [
            'legacy' => 'urbanOfficeQuestion1',
            'label' => 'Urbanistyka: zgodność z miejscowym planem zagospodarowania',
        ],
        'urban_office_question_2' => [
            'legacy' => 'urbanOfficeQuestion2',
            'label' => 'Urbanistyka: wymaga decyzji o warunkach zabudowy',
        ],
        'urban_office_result' => [
            'legacy' => 'urbanOfficeResult',
            'label' => 'Urbanistyka: projekt może zostać zrealizowany w lokalizacji',
        ],
        'antique_office_question_1' => [
            'legacy' => 'antiqueOfficeQuestion1',
            'label' => 'Zabytki: możliwy na terenie/obiekcie objętym ochroną zabytków',
        ],
        'antique_office_result' => [
            'legacy' => 'antiqueOfficeResult',
            'label' => 'Zabytki: projekt może zostać zrealizowany w lokalizacji',
        ],
    ];

    /**
     * @var array<string, array{legacy: string, label: string, comments?: bool}>
     */
    private const INITIAL_MERIT_TEXT_FIELDS = [
        'mayor_office_recommendation' => [
            'legacy' => 'mayorOfficeRecommendation',
            'label' => 'Prezydent: rekomendacja jednostki wiodącej',
            'comments' => true,
        ],
        'property_office_suboffice1_property_owner' => [
            'legacy' => 'propertyOfficeSuboffice1PropertyOwner',
            'label' => 'Majątek 1: właściciel albo użytkownik wieczysty działki',
            'comments' => true,
        ],
        'urban_office_information' => [
            'legacy' => 'urbanOfficeInformation',
            'label' => 'Urbanistyka: inne informacje istotne dla realizacji projektu',
        ],
    ];

    /**
     * @var array<string, array{legacy: string, label: string, comments?: bool}>
     */
    private const FINAL_MERIT_ANSWER_FIELDS = [
        'is_law_compliant' => [
            'legacy' => 'isLawCompliant',
            'label' => 'Zgodność z przepisami prawa w obszarze jednostki',
        ],
        'project_meet_requirements_universal_design' => [
            'legacy' => 'projectMeetRequirementsUniversalDesign',
            'label' => 'Uwzględnia projektowanie uniwersalne i wymagania dostępności',
        ],
        'is_project_feasible' => [
            'legacy' => 'isProjectFeasible',
            'label' => 'Możliwy do realizacji na terenie lub obiekcie objętym ochroną przyrody',
        ],
        'is_in_year_range' => [
            'legacy' => 'isInYearRange',
            'label' => 'Zakres pozwala na realizację w roku edycji',
        ],
        'can_start_in_year' => [
            'legacy' => 'canStartInYear',
            'label' => 'Realizacja inwestycji może rozpocząć się w roku edycji',
        ],
        'is_only_a_part' => [
            'legacy' => 'isOnlyAPart',
            'label' => 'Projekt jest tylko etapem większej inwestycji',
        ],
        'is_technology_available' => [
            'legacy' => 'isTechnologyAvailable',
            'label' => 'Istnieją możliwości techniczne realizacji projektu',
        ],
        'is_estimation_correct' => [
            'legacy' => 'isEstimationCorrect',
            'label' => 'Koszty projektu zostały prawidłowo oszacowane',
        ],
        'fits_in_budget' => [
            'legacy' => 'fitsInBudget',
            'label' => 'Urealniony koszt mieści się w puli właściwego obszaru',
        ],
        'above30percent' => [
            'legacy' => 'above30percent',
            'label' => 'Elementy niezwiązane z Zielonym SBO przekraczają 30% wartości',
        ],
        'has_additional_costs' => [
            'legacy' => 'hasAdditionalCosts',
            'label' => 'Projekt będzie generował koszty w kolejnych latach',
        ],
        'are_additional_costs_too_high' => [
            'legacy' => 'areAdditionalCostsTooHigh',
            'label' => 'Koszty utrzymania będą niewspółmiernie wysokie',
        ],
        'does_fit_thriftiness_requirement' => [
            'legacy' => 'doesFitThriftinessRequirement',
            'label' => 'Realizacja spełnia wymóg gospodarności',
        ],
        'generally_available_free_of_charge' => [
            'legacy' => 'generallyAvailableFreeOfCharge',
            'label' => 'Projekt spełnia wymogi ogólnodostępności i nieodpłatności',
        ],
        'was_task_modified' => [
            'legacy' => 'wasTaskModified',
            'label' => 'Jednostka wiodąca modyfikowała projekt z autorem',
        ],
        'lead_unit_request_opinion' => [
            'legacy' => 'leadUnitRequestOpinion',
            'label' => 'Jednostka wiodąca wystąpiła o opinie innych jednostek',
        ],
    ];

    /**
     * @var array<string, array{legacy: string, label: string, comments?: bool}>
     */
    private const FINAL_MERIT_TEXT_FIELDS = [
        'additional_information' => [
            'legacy' => 'additionalInformation',
            'label' => 'Dodatkowe informacje istotne dla dopuszczenia projektu pod głosowanie',
        ],
    ];

    /**
     * @var array<string, array{legacy: string, label: string, comments?: bool}>
     */
    private const CONSULTATION_TEXT_FIELDS = [
        'consultation_information' => [
            'legacy' => 'consultationInformation',
            'label' => 'Istotne informacje mogące mieć wpływ na realizację inwestycji',
        ],
    ];

    public static function getModelLabel(): string
    {
        return 'projekt';
    }

    public static function getPluralModelLabel(): string
    {
        return 'projekty';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('budget_edition_id')
                ->label('Edycja')
                ->options(fn (): array => BudgetEdition::query()
                    ->orderByDesc('propose_start')
                    ->pluck('id', 'id')
                    ->all())
                ->required(),
            Select::make('project_area_id')
                ->label('Obszar')
                ->options(fn (): array => ProjectArea::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->required(),
            TextInput::make('number')
                ->label('Numer')
                ->numeric(),
            TextInput::make('number_drawn')
                ->label('Numer wylosowany')
                ->numeric(),
            TextInput::make('title')
                ->label('Tytuł')
                ->required()
                ->maxLength(600),
            Select::make('status')
                ->label('Status')
                ->options(self::statusOptions())
                ->required(),
            Textarea::make('localization')
                ->label('Lokalizacja')
                ->columnSpanFull(),
            Textarea::make('description')
                ->label('Opis')
                ->columnSpanFull(),
            Textarea::make('goal')
                ->label('Cel')
                ->columnSpanFull(),
            Textarea::make('argumentation')
                ->label('Uzasadnienie')
                ->columnSpanFull(),
            TextInput::make('cost_formatted')
                ->label('Koszt')
                ->numeric(),
            Toggle::make('is_hidden')
                ->label('Ukryty publicznie'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stan operacyjny')
                    ->schema([
                        TextEntry::make('process_stage')
                            ->label('Etap procesu')
                            ->state(fn (Project $record): string => self::processStageLabel($record)),
                        TextEntry::make('next_step')
                            ->label('Najbliższa decyzja')
                            ->state(fn (Project $record): string => self::nextStepLabel($record)),
                        TextEntry::make('verification_progress')
                            ->label('Weryfikacje')
                            ->state(fn (Project $record): array => self::verificationProgress($record))
                            ->bulleted(),
                        TextEntry::make('voting_progress')
                            ->label('Głosowanie i decyzje')
                            ->state(fn (Project $record): array => self::votingProgress($record))
                            ->bulleted(),
                    ])
                    ->columns(4),
                Section::make('Podsumowanie')
                    ->schema([
                        TextEntry::make('number_drawn')
                            ->label('Numer do głosowania')
                            ->placeholder('Nie nadano'),
                        TextEntry::make('number')
                            ->label('Numer systemowy')
                            ->placeholder('Nie nadano'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (ProjectStatus $state): string => $state->adminLabel())
                            ->badge(),
                        TextEntry::make('submitted_at')
                            ->label('Data zgłoszenia')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('Nie wysłano do urzędu'),
                        TextEntry::make('title')
                            ->label('Tytuł')
                            ->columnSpanFull(),
                        TextEntry::make('summary_flags')
                            ->label('Oznaczenia')
                            ->state(fn (Project $record): array => self::projectFlags($record))
                            ->bulleted()
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make('Dane projektu')
                    ->schema([
                        TextEntry::make('budgetEdition.id')
                            ->label('Edycja')
                            ->placeholder('Nie podano'),
                        TextEntry::make('area.name')
                            ->label('Obszar')
                            ->placeholder('Całe miasto'),
                        TextEntry::make('category.name')
                            ->label('Kategoria główna')
                            ->placeholder('Nie podano'),
                        TextEntry::make('project_type')
                            ->label('Typ projektu')
                            ->state(fn (Project $record): string => self::projectTypeLabel($record)),
                        TextEntry::make('localization')
                            ->label('Lokalizacja')
                            ->placeholder('Nie podano')
                            ->columnSpanFull(),
                        TextEntry::make('address')
                            ->label('Adres')
                            ->placeholder('Nie podano'),
                        TextEntry::make('plot')
                            ->label('Działka')
                            ->placeholder('Nie podano'),
                        TextEntry::make('cost_formatted')
                            ->label('Koszt deklarowany')
                            ->money('PLN')
                            ->placeholder('Nie podano'),
                    ])
                    ->columns(4),
                Section::make('Opis i dostępność')
                    ->schema([
                        TextEntry::make('short_description')
                            ->label('Krótki opis')
                            ->placeholder('Nie podano')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Opis')
                            ->placeholder('Nie podano')
                            ->columnSpanFull(),
                        TextEntry::make('goal')
                            ->label('Cel')
                            ->placeholder('Nie podano')
                            ->columnSpanFull(),
                        TextEntry::make('argumentation')
                            ->label('Uzasadnienie')
                            ->placeholder('Nie podano')
                            ->columnSpanFull(),
                        TextEntry::make('availability')
                            ->label('Dostępność')
                            ->placeholder('Nie podano'),
                        TextEntry::make('recipients')
                            ->label('Odbiorcy')
                            ->placeholder('Nie podano'),
                        TextEntry::make('free_of_charge')
                            ->label('Nieodpłatność')
                            ->placeholder('Nie podano'),
                        TextEntry::make('additional_cost')
                            ->label('Koszty utrzymania')
                            ->placeholder('Nie podano'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Wnioskodawca i współautorzy')
                    ->schema([
                        TextEntry::make('author_summary')
                            ->label('Wnioskodawca')
                            ->state(fn (Project $record): array => self::authorSummary($record))
                            ->bulleted(),
                        TextEntry::make('coauthor_summary')
                            ->label('Współautorzy')
                            ->state(fn (Project $record): array => self::coauthorSummary($record))
                            ->bulleted(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Kosztorys i załączniki')
                    ->schema([
                        RepeatableEntry::make('cost_items_summary')
                            ->label('Pozycje kosztorysu')
                            ->state(fn (Project $record): array => self::costItemsSummary($record))
                            ->schema([
                                TextEntry::make('description')
                                    ->label('Opis'),
                                TextEntry::make('amount')
                                    ->label('Kwota'),
                            ])
                            ->columns(2),
                        RepeatableEntry::make('files_summary')
                            ->label('Załączniki')
                            ->state(fn (Project $record): array => self::filesSummary($record))
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Typ'),
                                TextEntry::make('name')
                                    ->label('Plik'),
                                TextEntry::make('privacy')
                                    ->label('Dostęp'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(1)
                    ->collapsible(),
                Section::make('Weryfikacja formalna')
                    ->schema([
                        TextEntry::make('formal_verification_summary')
                            ->label('Wyniki')
                            ->state(fn (Project $record): array => self::formalVerificationSummary($record))
                            ->bulleted()
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                Section::make('Weryfikacje urzędowe')
                    ->schema([
                        TextEntry::make('verification_assignments_summary')
                            ->label('Przydziały')
                            ->state(fn (Project $record): array => self::verificationAssignmentsSummary($record))
                            ->bulleted()
                            ->listWithLineBreaks(),
                        TextEntry::make('initial_merit_verification_summary')
                            ->label('Weryfikacja wstępna')
                            ->state(fn (Project $record): array => self::meritVerificationSummary($record, 'initial'))
                            ->bulleted()
                            ->listWithLineBreaks(),
                        TextEntry::make('final_merit_verification_summary')
                            ->label('Weryfikacja merytoryczna')
                            ->state(fn (Project $record): array => self::meritVerificationSummary($record, 'final'))
                            ->bulleted()
                            ->listWithLineBreaks(),
                        TextEntry::make('consultation_verification_summary')
                            ->label('Konsultacje')
                            ->state(fn (Project $record): array => self::meritVerificationSummary($record, 'consultation'))
                            ->bulleted()
                            ->listWithLineBreaks(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Głosowania, odwołania i historia kart')
                    ->schema([
                        TextEntry::make('board_votes_summary')
                            ->label('Głosowania')
                            ->state(fn (Project $record): array => self::boardVotesSummary($record))
                            ->bulleted()
                            ->listWithLineBreaks(),
                        TextEntry::make('appeal_summary')
                            ->label('Odwołanie')
                            ->state(fn (Project $record): array => self::appealSummary($record))
                            ->bulleted()
                            ->listWithLineBreaks(),
                        TextEntry::make('verification_versions_summary')
                            ->label('Wersje kart')
                            ->state(fn (Project $record): array => self::verificationVersionsSummary($record))
                            ->bulleted()
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number_drawn')
                    ->label('Nr')
                    ->sortable(),
                TextColumn::make('number')
                    ->label('Nr systemowy')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('budgetEdition.id')
                    ->label('Edycja')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('area.name')
                    ->label('Obszar')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategoria')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->adminLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('creator.email')
                    ->label('Wnioskodawca')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submitted_at')
                    ->label('Zgłoszono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cost_formatted')
                    ->label('Koszt')
                    ->money('PLN')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('localization')
                    ->label('Lokalizacja')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_support_list')
                    ->label('Lista poparcia')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'tak' : 'nie')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('need_correction')
                    ->label('Korekta')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'wymagana' : 'nie')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(self::statusOptions())
                    ->multiple(),
                SelectFilter::make('budget_edition_id')
                    ->label('Edycja')
                    ->relationship('budgetEdition', 'id')
                    ->searchable(),
                SelectFilter::make('project_area_id')
                    ->label('Obszar')
                    ->relationship('area', 'name')
                    ->searchable(),
                SelectFilter::make('category_id')
                    ->label('Kategoria')
                    ->relationship('category', 'name')
                    ->searchable(),
                SelectFilter::make('local')
                    ->label('Typ projektu')
                    ->options([
                        1 => 'Projekt lokalny',
                        2 => 'Projekt Zielonego BO',
                    ]),
                SelectFilter::make('is_support_list')
                    ->label('Lista poparcia')
                    ->options([
                        1 => 'Tak',
                        0 => 'Nie',
                    ]),
                SelectFilter::make('need_correction')
                    ->label('Wymaga korekty')
                    ->options([
                        1 => 'Tak',
                        0 => 'Nie',
                    ]),
                SelectFilter::make('is_hidden')
                    ->label('Widoczność')
                    ->options([
                        0 => 'Widoczny publicznie',
                        1 => 'Ukryty publicznie',
                    ]),
                Filter::make('submitted_between')
                    ->label('Data zgłoszenia')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Zgłoszono od'),
                        DatePicker::make('until')
                            ->label('Zgłoszono do'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('submitted_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('submitted_at', '<=', $date))),
                Filter::make('cost_between')
                    ->label('Koszt')
                    ->schema([
                        TextInput::make('min')
                            ->label('Koszt od')
                            ->numeric(),
                        TextInput::make('max')
                            ->label('Koszt do')
                            ->numeric(),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['min'] ?? null, fn (Builder $query, string $amount): Builder => $query->where('cost_formatted', '>=', $amount))
                        ->when($data['max'] ?? null, fn (Builder $query, string $amount): Builder => $query->where('cost_formatted', '<=', $amount))),
                Filter::make('project_text')
                    ->label('Dane projektu')
                    ->schema([
                        TextInput::make('number')
                            ->label('Numer'),
                        TextInput::make('author')
                            ->label('Autor / e-mail'),
                        TextInput::make('location')
                            ->label('Lokalizacja/adres/działka'),
                    ])
                    ->columns(3)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['number'] ?? null, function (Builder $query, string $number): Builder {
                            return $query->where(function (Builder $query) use ($number): void {
                                if (is_numeric($number)) {
                                    $query->where('number', (int) $number)
                                        ->orWhere('number_drawn', (int) $number);
                                }
                            });
                        })
                        ->when($data['author'] ?? null, function (Builder $query, string $author): Builder {
                            return $query->where(function (Builder $query) use ($author): void {
                                $query->where('authors->first_name', 'like', "%{$author}%")
                                    ->orWhere('authors->last_name', 'like', "%{$author}%")
                                    ->orWhere('authors->email', 'like', "%{$author}%")
                                    ->orWhereHas('creator', fn (Builder $query): Builder => $query->where('email', 'like', "%{$author}%"));
                            });
                        })
                        ->when($data['location'] ?? null, function (Builder $query, string $location): Builder {
                            return $query->where(function (Builder $query) use ($location): void {
                                $query->where('localization', 'like', "%{$location}%")
                                    ->orWhere('address', 'like', "%{$location}%")
                                    ->orWhere('plot', 'like', "%{$location}%");
                            });
                        })),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'md' => 2,
                'xl' => 4,
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                self::beginFormalVerificationAction(),
                self::acceptFormalVerificationAction(),
                self::rejectFormalVerificationAction(),
                self::requestFormalCorrectionAction(),
                self::forwardFormalVerificationAction(),
                self::startProjectCorrectionAction(),
                self::applyProjectCorrectionAction(),
                self::assignMeritDepartmentsAction(),
                self::submitInitialMeritVerificationAction(),
                self::submitFinalMeritVerificationAction(),
                self::submitConsultationVerificationAction(),
                self::returnVerificationCardAction(),
                self::verificationOverviewAction(),
                self::castBoardVoteAction(BoardType::Zk),
                self::castBoardVoteAction(BoardType::Ot),
                self::castBoardVoteAction(BoardType::At),
                self::submitProjectAppealAction(),
                self::decideProjectAppealAction(),
                self::respondProjectAppealAction(),
                self::closeBoardVotingAction(BoardType::Ot),
                self::restartBoardVotingAction(BoardType::Ot),
                self::closeBoardVotingAction(BoardType::At),
                self::restartBoardVotingAction(BoardType::At),
                DeleteAction::make(),
            ]);
    }

    public static function canBeginFormalVerification(Project $project): bool
    {
        return self::canManageFormalVerification()
            && $project->status === ProjectStatus::Submitted;
    }

    public static function canCompleteFormalVerification(Project $project): bool
    {
        return self::canManageFormalVerification()
            && in_array($project->status, [
                ProjectStatus::Submitted,
                ProjectStatus::DuringFormalVerification,
            ], true);
    }

    public static function canRequestFormalCorrection(Project $project): bool
    {
        return self::canManageFormalVerification()
            && in_array($project->status, [
                ProjectStatus::Submitted,
                ProjectStatus::DuringFormalVerification,
            ], true);
    }

    public static function canForwardFormalVerification(Project $project): bool
    {
        return self::canManageFormalVerification()
            && $project->status === ProjectStatus::FormallyVerified;
    }

    public static function canAssignMeritDepartments(Project $project): bool
    {
        return self::canManageMeritVerification()
            && in_array($project->status, [
                ProjectStatus::FormallyVerified,
                ProjectStatus::DuringInitialVerification,
                ProjectStatus::SentForMeritVerification,
                ProjectStatus::DuringMeritVerification,
            ], true);
    }

    public static function canSubmitInitialMeritVerification(Project $project): bool
    {
        return self::canManageMeritVerification()
            && in_array($project->status, [
                ProjectStatus::FormallyVerified,
                ProjectStatus::DuringInitialVerification,
            ], true);
    }

    public static function canSubmitFinalMeritVerification(Project $project): bool
    {
        return self::canManageMeritVerification()
            && in_array($project->status, [
                ProjectStatus::SentForMeritVerification,
                ProjectStatus::DuringMeritVerification,
            ], true);
    }

    public static function canSubmitConsultationVerification(Project $project): bool
    {
        return self::canManageMeritVerification()
            && in_array($project->status, [
                ProjectStatus::SentForMeritVerification,
                ProjectStatus::DuringMeritVerification,
            ], true);
    }

    public static function canReturnVerificationCard(Project $project): bool
    {
        return self::canManageMeritVerification()
            && $project->verificationAssignments()
                ->whereIn('type', [
                    VerificationAssignmentType::MeritInitial->value,
                    VerificationAssignmentType::MeritFinish->value,
                    VerificationAssignmentType::Consultation->value,
                ])
                ->whereNotNull('sent_at')
                ->exists();
    }

    public static function canStartProjectCorrection(Project $project): bool
    {
        return self::canManageProjectCorrections()
            && $project->status !== ProjectStatus::WorkingCopy;
    }

    public static function canViewVerificationOverview(Project $project): bool
    {
        return (self::canManageFormalVerification() || self::canManageMeritVerification())
            && $project->status !== ProjectStatus::WorkingCopy;
    }

    public static function canApplyProjectCorrection(Project $project): bool
    {
        return self::canManageProjectCorrections()
            && $project->need_correction;
    }

    public static function beginFormalVerificationFromAdmin(Project $project): Project
    {
        return app(BeginFormalVerificationAction::class)->execute(
            $project,
            self::authenticatedUser('verification.formal.begin.rejected_guest'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function completeFormalVerificationFromAdminForm(Project $project, array $data, bool $result): FormalVerification
    {
        return app(CompleteFormalVerificationAction::class)->execute(
            $project,
            self::authenticatedUser('verification.formal.complete.rejected_guest'),
            $result,
            self::formalAnswersFromData($data),
            $data['result_comments'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function requestFormalCorrectionFromAdminForm(Project $project, array $data): ProjectCorrection
    {
        return app(RequestFormalCorrectionAction::class)->execute(
            $project,
            self::authenticatedUser('verification.formal.correction.rejected_guest'),
            self::correctionFieldsFromData($data),
            $data['notes'] ?? null,
            self::optionalDateTime($data['deadline'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function forwardFormalVerificationFromAdminForm(Project $project, array $data): Project
    {
        return app(ForwardFormalVerificationToInitialVerificationAction::class)->execute(
            $project,
            self::authenticatedUser('verification.formal.forward_initial.rejected_guest'),
            self::departmentsFromData($data),
            self::optionalDateTime($data['deadline'] ?? null),
            $data['notes'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<VerificationAssignment>
     */
    public static function assignMeritDepartmentsFromAdminForm(Project $project, array $data): array
    {
        $assignments = [];
        $type = VerificationAssignmentType::from((int) $data['type']);

        foreach (self::departmentsFromData($data) as $department) {
            $assignments[] = app(AssignVerificationDepartmentAction::class)->execute(
                $project,
                $department,
                $type,
                self::optionalDateTime($data['deadline'] ?? null),
                $data['notes'] ?? null,
            );
        }

        return $assignments;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function submitInitialMeritVerificationFromAdminForm(Project $project, array $data): InitialMeritVerification
    {
        return app(SubmitInitialMeritVerificationAction::class)->execute(
            $project,
            self::departmentFromData($data),
            self::authenticatedUser('verification.initial.submit.rejected_guest'),
            (bool) ($data['result'] ?? false),
            self::meritAnswersFromData($data, self::INITIAL_MERIT_ANSWER_FIELDS, self::INITIAL_MERIT_TEXT_FIELDS),
            $data['result_comments'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function submitFinalMeritVerificationFromAdminForm(Project $project, array $data): FinalMeritVerification
    {
        return app(SubmitFinalMeritVerificationAction::class)->execute(
            $project,
            self::departmentFromData($data),
            self::authenticatedUser('verification.final.submit.rejected_guest'),
            (bool) ($data['result'] ?? false),
            self::meritAnswersFromData($data, self::FINAL_MERIT_ANSWER_FIELDS, self::FINAL_MERIT_TEXT_FIELDS),
            $data['result_comments'] ?? null,
            self::costRowsFromData($data, 'corrected'),
            self::costRowsFromData($data, 'future'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function submitConsultationVerificationFromAdminForm(Project $project, array $data): ConsultationVerification
    {
        return app(SubmitConsultationVerificationAction::class)->execute(
            $project,
            self::departmentFromData($data),
            self::authenticatedUser('verification.consultation.submit.rejected_guest'),
            (bool) ($data['result'] ?? false),
            self::meritAnswersFromData($data, textFields: self::CONSULTATION_TEXT_FIELDS),
            $data['result_comments'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function returnVerificationCardFromAdminForm(
        Project $project,
        array $data,
    ): InitialMeritVerification|FinalMeritVerification|ConsultationVerification {
        $type = VerificationAssignmentType::from((int) $data['type']);
        $department = self::departmentFromData($data);
        $verification = self::returnableVerification($project, $department, $type);

        return app(ReturnVerificationCardAction::class)->execute(
            $verification,
            self::authenticatedUser('verification.card.return.rejected_guest'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function startProjectCorrectionFromAdminForm(Project $project, array $data): ProjectCorrection
    {
        return app(StartCorrectionAction::class)->execute(
            $project,
            self::authenticatedUser('project.correction.rejected_guest'),
            self::correctionFieldsFromData($data),
            $data['notes'] ?? null,
            self::optionalDateTime($data['deadline'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function applyProjectCorrectionFromAdminForm(Project $project, array $data): Project
    {
        return app(ApplyCorrectionAction::class)->execute(
            $project,
            self::authenticatedUser('project.correction.apply.rejected_guest'),
            self::correctionAttributesFromData($data),
        );
    }

    public static function verificationOverviewFormData(Project $project): array
    {
        $service = app(VerificationOverviewService::class);

        return [
            'verification_overview' => $service->overviewText($project),
            'verification_versions' => $service->versionsText($project),
        ];
    }

    public static function canCastBoardVote(Project $project, BoardType $boardType): bool
    {
        $userId = Auth::id();

        if ($userId === null || ! Gate::allows('cast-board-vote', $boardType)) {
            return false;
        }

        $statusMatches = match ($boardType) {
            BoardType::Zk, BoardType::Ot => $project->status === ProjectStatus::DuringTeamVerification,
            BoardType::At => $project->status === ProjectStatus::DuringTeamRecallVerification,
        };

        if (! $statusMatches) {
            return false;
        }

        return ! $project->boardVotes()
            ->where('user_id', $userId)
            ->where('board_type', $boardType->value)
            ->exists();
    }

    public static function canCloseBoardVoting(Project $project, BoardType $boardType): bool
    {
        if (! Gate::allows('manage-board-voting')) {
            return false;
        }

        return match ($boardType) {
            BoardType::Ot => $project->status === ProjectStatus::DuringTeamVerification,
            BoardType::At => $project->status === ProjectStatus::DuringTeamRecallVerification,
            BoardType::Zk => false,
        };
    }

    public static function canRestartBoardVoting(Project $project, BoardType $boardType): bool
    {
        if (! Gate::allows('manage-board-voting')) {
            return false;
        }

        return match ($boardType) {
            BoardType::Ot => in_array($project->status, [
                ProjectStatus::DuringTeamVerification,
                ProjectStatus::TeamClosedVerification,
            ], true),
            BoardType::At => in_array($project->status, [
                ProjectStatus::DuringTeamRecallVerification,
                ProjectStatus::TeamRecallClosedVerification,
            ], true),
            BoardType::Zk => false,
        };
    }

    public static function canSubmitProjectAppeal(Project $project): bool
    {
        return self::canManageProjects()
            && $project->status->isRejected()
            && ! $project->appeal()->exists();
    }

    public static function canDecideProjectAppeal(Project $project): bool
    {
        return self::canManageProjects()
            && $project->appeal()
                ->where('first_decision', ProjectAppealFirstDecision::Pending->value)
                ->exists();
    }

    public static function canRespondProjectAppeal(Project $project): bool
    {
        return self::canManageProjects()
            && $project->appeal()->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function submitProjectAppealFromAdminForm(Project $project, array $data): ProjectAppeal
    {
        return app(SubmitProjectAppealAction::class)->execute(
            $project,
            self::authenticatedUser('project.appeal.submit.rejected_guest'),
            (string) ($data['appeal_message'] ?? ''),
            true,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function decideProjectAppealFromAdminForm(Project $project, array $data): ProjectAppeal
    {
        return app(DecideProjectAppealAction::class)->execute(
            self::appealFromProject($project),
            self::authenticatedUser('project.appeal.decision.rejected_guest'),
            ProjectAppealFirstDecision::from((int) $data['first_decision']),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function respondProjectAppealFromAdminForm(Project $project, array $data): ProjectAppeal
    {
        return app(RespondProjectAppealAction::class)->execute(
            self::appealFromProject($project),
            self::authenticatedUser('project.appeal.response.rejected_guest'),
            (string) ($data['response_to_appeal'] ?? ''),
        );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    private static function statusOptions(): array
    {
        $options = [];

        foreach (ProjectStatus::cases() as $status) {
            $options[$status->value] = $status->adminLabel();
        }

        return $options;
    }

    private static function castBoardVoteAction(BoardType $boardType): Action
    {
        return Action::make('cast_'.strtolower($boardType->value).'_board_vote')
            ->label('Głos '.$boardType->value)
            ->schema([
                Select::make('choice')
                    ->label('Głos')
                    ->options(self::boardVoteChoiceOptions($boardType))
                    ->required(),
                Textarea::make('comment')
                    ->label('Komentarz')
                    ->maxLength(5000),
            ])
            ->visible(fn (Project $record): bool => self::canCastBoardVote($record, $boardType))
            ->action(function (array $data, Project $record) use ($boardType): void {
                app(CastProjectBoardVoteAction::class)->execute(
                    $record,
                    self::authenticatedUser('verification.board.vote.rejected_guest'),
                    $boardType,
                    (int) $data['choice'],
                    $data['comment'] ?? null,
                );
            });
    }

    private static function beginFormalVerificationAction(): Action
    {
        return Action::make('begin_formal_verification')
            ->label('Rozpocznij formalną')
            ->requiresConfirmation()
            ->visible(fn (Project $record): bool => self::canBeginFormalVerification($record))
            ->action(fn (Project $record): Project => self::beginFormalVerificationFromAdmin($record));
    }

    private static function acceptFormalVerificationAction(): Action
    {
        return Action::make('accept_formal_verification')
            ->label('Formalnie OK')
            ->schema(self::formalVerificationAnswerSchema())
            ->visible(fn (Project $record): bool => self::canCompleteFormalVerification($record))
            ->action(fn (array $data, Project $record): FormalVerification => self::completeFormalVerificationFromAdminForm($record, $data, true));
    }

    private static function rejectFormalVerificationAction(): Action
    {
        return Action::make('reject_formal_verification')
            ->label('Odrzuć formalnie')
            ->schema([
                ...self::formalVerificationAnswerSchema(),
                Textarea::make('result_comments')
                    ->label('Uzasadnienie')
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canCompleteFormalVerification($record))
            ->action(fn (array $data, Project $record): FormalVerification => self::completeFormalVerificationFromAdminForm($record, $data, false));
    }

    private static function requestFormalCorrectionAction(): Action
    {
        return Action::make('request_formal_correction')
            ->label('Korekta formalna')
            ->schema([
                CheckboxList::make('allowed_fields')
                    ->label('Pola do poprawy')
                    ->options(self::correctionFieldOptions())
                    ->required(),
                DateTimePicker::make('deadline')
                    ->label('Termin korekty'),
                Textarea::make('notes')
                    ->label('Uwagi dla wnioskodawcy')
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canRequestFormalCorrection($record))
            ->action(fn (array $data, Project $record): ProjectCorrection => self::requestFormalCorrectionFromAdminForm($record, $data));
    }

    private static function forwardFormalVerificationAction(): Action
    {
        return Action::make('forward_formal_verification')
            ->label('Do weryfikacji wstępnej')
            ->schema([
                Select::make('department_ids')
                    ->label('Jednostki')
                    ->multiple()
                    ->options(fn (): array => Department::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->required(),
                DateTimePicker::make('deadline')
                    ->label('Termin'),
                Textarea::make('notes')
                    ->label('Uwagi')
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canForwardFormalVerification($record))
            ->action(fn (array $data, Project $record): Project => self::forwardFormalVerificationFromAdminForm($record, $data));
    }

    private static function startProjectCorrectionAction(): Action
    {
        return Action::make('start_project_correction')
            ->label('Wezwij do korekty')
            ->schema([
                CheckboxList::make('allowed_fields')
                    ->label('Pola do poprawy')
                    ->options(self::correctionFieldOptions())
                    ->required(),
                DateTimePicker::make('deadline')
                    ->label('Termin korekty'),
                Textarea::make('notes')
                    ->label('Uwagi dla wnioskodawcy')
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canStartProjectCorrection($record))
            ->action(fn (array $data, Project $record): ProjectCorrection => self::startProjectCorrectionFromAdminForm($record, $data));
    }

    private static function applyProjectCorrectionAction(): Action
    {
        return Action::make('apply_project_correction')
            ->label('Zastosuj korektę')
            ->fillForm(fn (Project $record): array => self::correctionFormData($record))
            ->schema(self::projectCorrectionSchema())
            ->visible(fn (Project $record): bool => self::canApplyProjectCorrection($record))
            ->action(fn (array $data, Project $record): Project => self::applyProjectCorrectionFromAdminForm($record, $data));
    }

    private static function assignMeritDepartmentsAction(): Action
    {
        return Action::make('assign_merit_departments')
            ->label('Przydziel jednostki')
            ->schema([
                Select::make('type')
                    ->label('Typ weryfikacji')
                    ->options(self::verificationAssignmentTypeOptions())
                    ->required(),
                Select::make('department_ids')
                    ->label('Jednostki')
                    ->multiple()
                    ->options(fn (): array => self::departmentOptions())
                    ->required(),
                DateTimePicker::make('deadline')
                    ->label('Termin'),
                Textarea::make('notes')
                    ->label('Uwagi')
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canAssignMeritDepartments($record))
            ->action(fn (array $data, Project $record): array => self::assignMeritDepartmentsFromAdminForm($record, $data));
    }

    private static function submitInitialMeritVerificationAction(): Action
    {
        return Action::make('submit_initial_merit_verification')
            ->label('Wyślij wstępną')
            ->schema(self::initialMeritVerificationSchema())
            ->visible(fn (Project $record): bool => self::canSubmitInitialMeritVerification($record))
            ->action(fn (array $data, Project $record): InitialMeritVerification => self::submitInitialMeritVerificationFromAdminForm($record, $data));
    }

    private static function submitFinalMeritVerificationAction(): Action
    {
        return Action::make('submit_final_merit_verification')
            ->label('Wyślij końcową')
            ->schema([
                ...self::finalMeritVerificationSchema(),
                Repeater::make('corrected_costs')
                    ->label('Koszty szacunkowe')
                    ->schema(self::verificationCostRowSchema())
                    ->defaultItems(0)
                    ->addActionLabel('Dodaj koszt szacunkowy')
                    ->columns(2)
                    ->columnSpanFull(),
                Repeater::make('future_costs')
                    ->label('Koszty w kolejnych latach')
                    ->schema(self::verificationCostRowSchema())
                    ->defaultItems(0)
                    ->addActionLabel('Dodaj koszt przyszły')
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canSubmitFinalMeritVerification($record))
            ->action(fn (array $data, Project $record): FinalMeritVerification => self::submitFinalMeritVerificationFromAdminForm($record, $data));
    }

    private static function submitConsultationVerificationAction(): Action
    {
        return Action::make('submit_consultation_verification')
            ->label('Wyślij konsultację')
            ->schema(self::consultationVerificationSchema())
            ->visible(fn (Project $record): bool => self::canSubmitConsultationVerification($record))
            ->action(fn (array $data, Project $record): ConsultationVerification => self::submitConsultationVerificationFromAdminForm($record, $data));
    }

    private static function returnVerificationCardAction(): Action
    {
        return Action::make('return_verification_card')
            ->label('Cofnij kartę')
            ->schema([
                Select::make('type')
                    ->label('Typ karty')
                    ->options(self::verificationAssignmentTypeOptions())
                    ->required(),
                Select::make('department_id')
                    ->label('Jednostka')
                    ->options(fn (Project $record): array => self::returnableVerificationDepartmentOptions($record))
                    ->required(),
            ])
            ->visible(fn (Project $record): bool => self::canReturnVerificationCard($record))
            ->action(fn (array $data, Project $record): InitialMeritVerification|FinalMeritVerification|ConsultationVerification => self::returnVerificationCardFromAdminForm($record, $data));
    }

    private static function verificationOverviewAction(): Action
    {
        return Action::make('verification_overview')
            ->label('Historia weryfikacji')
            ->fillForm(fn (Project $record): array => self::verificationOverviewFormData($record))
            ->schema([
                Textarea::make('verification_overview')
                    ->label('Przydziały i karty')
                    ->rows(12)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Textarea::make('verification_versions')
                    ->label('Wersje kart')
                    ->rows(8)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Zamknij')
            ->visible(fn (Project $record): bool => self::canViewVerificationOverview($record));
    }

    private static function submitProjectAppealAction(): Action
    {
        return Action::make('submit_project_appeal')
            ->label('Dodaj odwołanie')
            ->schema([
                Textarea::make('appeal_message')
                    ->label('Treść odwołania')
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canSubmitProjectAppeal($record))
            ->action(fn (array $data, Project $record): ProjectAppeal => self::submitProjectAppealFromAdminForm($record, $data));
    }

    private static function decideProjectAppealAction(): Action
    {
        return Action::make('decide_project_appeal')
            ->label('Decyzja wstępna odwołania')
            ->schema([
                Select::make('first_decision')
                    ->label('Decyzja')
                    ->options([
                        ProjectAppealFirstDecision::Rejected->value => 'Odrzuć odwołanie',
                        ProjectAppealFirstDecision::Accepted->value => 'Przyjmij do ponownej oceny',
                    ])
                    ->required(),
            ])
            ->visible(fn (Project $record): bool => self::canDecideProjectAppeal($record))
            ->action(fn (array $data, Project $record): ProjectAppeal => self::decideProjectAppealFromAdminForm($record, $data));
    }

    private static function respondProjectAppealAction(): Action
    {
        return Action::make('respond_project_appeal')
            ->label('Odpowiedz na odwołanie')
            ->schema([
                Textarea::make('response_to_appeal')
                    ->label('Odpowiedź komisji')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->visible(fn (Project $record): bool => self::canRespondProjectAppeal($record))
            ->action(fn (array $data, Project $record): ProjectAppeal => self::respondProjectAppealFromAdminForm($record, $data));
    }

    private static function closeBoardVotingAction(BoardType $boardType): Action
    {
        return Action::make('close_'.strtolower($boardType->value).'_board_voting')
            ->label('Zamknij '.$boardType->value)
            ->requiresConfirmation()
            ->visible(fn (Project $record): bool => self::canCloseBoardVoting($record, $boardType))
            ->action(fn (Project $record): Project => app(CloseBoardVotingAction::class)->execute($record, $boardType));
    }

    private static function restartBoardVotingAction(BoardType $boardType): Action
    {
        return Action::make('restart_'.strtolower($boardType->value).'_board_voting')
            ->label('Restart '.$boardType->value)
            ->requiresConfirmation()
            ->visible(fn (Project $record): bool => self::canRestartBoardVoting($record, $boardType))
            ->action(fn (Project $record): Project => app(RestartBoardVotingAction::class)->execute($record, $boardType));
    }

    private static function boardVoteChoiceOptions(BoardType $boardType): array
    {
        return match ($boardType) {
            BoardType::Zk => [
                ZkVoteChoice::Up->value => 'Za',
                ZkVoteChoice::Down->value => 'Przeciw',
            ],
            BoardType::Ot => [
                OtVoteChoice::Withhold->value => 'Wstrzymuje się',
                OtVoteChoice::VerifyAgain->value => 'Do ponownej weryfikacji',
                OtVoteChoice::RejectedWithRecall->value => 'Odrzucony z możliwością odwołania',
                OtVoteChoice::Accepted->value => 'Zatwierdzony na listę',
            ],
            BoardType::At => [
                AtVoteChoice::Withhold->value => 'Wstrzymuje się',
                AtVoteChoice::AcceptedToVote->value => 'Zatwierdzony na listę',
                AtVoteChoice::Rejected->value => 'Odrzucony ostatecznie',
            ],
        };
    }

    /**
     * @return array<int, mixed>
     */
    private static function initialMeritVerificationSchema(): array
    {
        return [
            ...self::meritVerificationHeaderSchema(),
            ...self::legacyMeritAnswerSchema(self::INITIAL_MERIT_ANSWER_FIELDS, self::INITIAL_MERIT_TEXT_FIELDS),
            ...self::meritVerificationFooterSchema(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function finalMeritVerificationSchema(): array
    {
        return [
            ...self::meritVerificationHeaderSchema(),
            ...self::legacyMeritAnswerSchema(self::FINAL_MERIT_ANSWER_FIELDS, self::FINAL_MERIT_TEXT_FIELDS),
            ...self::meritVerificationFooterSchema(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function consultationVerificationSchema(): array
    {
        return [
            ...self::meritVerificationHeaderSchema(),
            ...self::legacyMeritAnswerSchema([], self::CONSULTATION_TEXT_FIELDS),
            ...self::meritVerificationFooterSchema(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function meritVerificationHeaderSchema(): array
    {
        return [
            Select::make('department_id')
                ->label('Jednostka')
                ->options(fn (): array => self::departmentOptions())
                ->required(),
            Toggle::make('result')
                ->label('Wynik pozytywny')
                ->default(true),
        ];
    }

    /**
     * @param  array<string, array{legacy: string, label: string, comments?: bool}>  $answerFields
     * @param  array<string, array{legacy: string, label: string, comments?: bool}>  $textFields
     * @return array<int, mixed>
     */
    private static function legacyMeritAnswerSchema(array $answerFields, array $textFields): array
    {
        $schema = [];

        foreach ($answerFields as $fieldName => $definition) {
            $schema[] = Select::make($fieldName)
                ->label($definition['label'])
                ->options(self::MERIT_VALUE_OPTIONS);

            if (($definition['comments'] ?? true) === true) {
                $schema[] = Textarea::make($fieldName.'_comments')
                    ->label('Uwagi')
                    ->maxLength(63000)
                    ->columnSpanFull();
            }
        }

        foreach ($textFields as $fieldName => $definition) {
            $schema[] = Textarea::make($fieldName)
                ->label($definition['label'])
                ->maxLength(63000)
                ->columnSpanFull();

            if (($definition['comments'] ?? false) === true) {
                $schema[] = Textarea::make($fieldName.'_comments')
                    ->label('Uwagi')
                    ->maxLength(63000)
                    ->columnSpanFull();
            }
        }

        return $schema;
    }

    /**
     * @return array<int, mixed>
     */
    private static function meritVerificationFooterSchema(): array
    {
        return [
            Textarea::make('answers_notes')
                ->label('Treść opinii')
                ->maxLength(5000)
                ->columnSpanFull(),
            Textarea::make('result_comments')
                ->label('Uzasadnienie wyniku negatywnego')
                ->maxLength(5000)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function verificationCostRowSchema(): array
    {
        return [
            TextInput::make('description')
                ->label('Opis')
                ->maxLength(1000),
            TextInput::make('sum')
                ->label('Kwota')
                ->numeric(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function projectCorrectionSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Tytuł')
                ->maxLength(600),
            Select::make('project_area_id')
                ->label('Obszar')
                ->options(fn (): array => ProjectArea::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all()),
            Select::make('category_id')
                ->label('Kategoria główna')
                ->options(fn (): array => Category::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all()),
            Textarea::make('localization')
                ->label('Lokalizacja')
                ->columnSpanFull(),
            Textarea::make('goal')
                ->label('Cel')
                ->columnSpanFull(),
            Textarea::make('description')
                ->label('Opis')
                ->columnSpanFull(),
            Textarea::make('argumentation')
                ->label('Uzasadnienie')
                ->columnSpanFull(),
            Textarea::make('availability')
                ->label('Dostępność')
                ->columnSpanFull(),
            Textarea::make('recipients')
                ->label('Odbiorcy')
                ->columnSpanFull(),
            Textarea::make('free_of_charge')
                ->label('Nieodpłatność')
                ->columnSpanFull(),
        ];
    }

    private static function verificationAssignmentTypeOptions(): array
    {
        return [
            VerificationAssignmentType::MeritInitial->value => 'Weryfikacja wstępna',
            VerificationAssignmentType::MeritFinish->value => 'Weryfikacja końcowa',
            VerificationAssignmentType::Consultation->value => 'Konsultacja',
        ];
    }

    private static function returnableVerificationDepartmentOptions(Project $project): array
    {
        return $project->verificationAssignments()
            ->whereNotNull('sent_at')
            ->with('department')
            ->get()
            ->filter(fn (VerificationAssignment $assignment): bool => $assignment->department instanceof Department)
            ->mapWithKeys(fn (VerificationAssignment $assignment): array => [
                $assignment->department->id => $assignment->department->name,
            ])
            ->sort()
            ->all();
    }

    private static function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function formalVerificationFieldLabels(): array
    {
        return collect(self::formalAnswerFields())
            ->mapWithKeys(fn (array $definition, string $fieldName): array => [
                $fieldName => $definition['label'],
            ])
            ->all();
    }

    /**
     * @return array<string, array{legacy: string, label: string}>
     */
    private static function formalAnswerFields(): array
    {
        return LegacyProjectFormText::formalAnswerFields();
    }

    /**
     * @return array<int, mixed>
     */
    private static function formalVerificationAnswerSchema(): array
    {
        $schema = [];

        foreach (self::formalAnswerFields() as $fieldName => $definition) {
            $schema[] = Toggle::make($fieldName)
                ->label($definition['label']);
            $schema[] = Textarea::make($fieldName.'_comments')
                ->label('Uwagi')
                ->maxLength(63000)
                ->columnSpanFull();
        }

        $schema[] = Select::make('is_project_category')
            ->label('Weryfikowany projekt jest')
            ->options([
                1 => 'Projektem infrastrukturalnym',
                2 => 'Projektem nieinfrastrukturalnym',
                3 => 'Projektem mieszanym',
            ]);

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int|string>
     */
    private static function formalAnswersFromData(array $data): array
    {
        $answers = [];

        foreach (self::formalAnswerFields() as $fieldName => $definition) {
            $legacyField = $definition['legacy'];
            $answers[$legacyField] = (bool) ($data[$fieldName] ?? false) ? 1 : 0;

            $comment = trim((string) ($data[$fieldName.'_comments'] ?? ''));
            if ($comment !== '') {
                $answers[$legacyField.'Comments'] = $comment;
            }
        }

        if (($data['is_project_category'] ?? null) !== null && $data['is_project_category'] !== '') {
            $answers['isProjectCategory'] = (int) $data['is_project_category'];
        }

        return $answers;
    }

    private static function correctionFieldOptions(): array
    {
        return [
            ProjectCorrectionField::Title->value => 'Tytuł',
            ProjectCorrectionField::ProjectArea->value => 'Obszar',
            ProjectCorrectionField::Localization->value => 'Lokalizacja',
            ProjectCorrectionField::MapData->value => 'Mapa',
            ProjectCorrectionField::Goal->value => 'Cel',
            ProjectCorrectionField::Description->value => 'Opis',
            ProjectCorrectionField::Argumentation->value => 'Uzasadnienie',
            ProjectCorrectionField::Availability->value => 'Dostępność',
            ProjectCorrectionField::Category->value => 'Kategoria',
            ProjectCorrectionField::Recipients->value => 'Odbiorcy',
            ProjectCorrectionField::FreeOfCharge->value => 'Nieodpłatność',
            ProjectCorrectionField::Cost->value => 'Koszt',
            ProjectCorrectionField::SupportAttachment->value => 'Lista poparcia',
            ProjectCorrectionField::AgreementAttachment->value => 'Zgoda właściciela',
            ProjectCorrectionField::MapAttachment->value => 'Załącznik mapy',
            ProjectCorrectionField::ParentAgreementAttachment->value => 'Zgoda rodzica',
            ProjectCorrectionField::Attachments->value => 'Pozostałe załączniki',
        ];
    }

    private static function correctionFormData(Project $project): array
    {
        return [
            'title' => $project->title,
            'project_area_id' => $project->project_area_id,
            'category_id' => $project->category_id,
            'localization' => $project->localization,
            'goal' => $project->goal,
            'description' => $project->description,
            'argumentation' => $project->argumentation,
            'availability' => $project->availability,
            'recipients' => $project->recipients,
            'free_of_charge' => $project->free_of_charge,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<ProjectCorrectionField>
     */
    private static function correctionFieldsFromData(array $data): array
    {
        return array_map(
            static fn (string $field): ProjectCorrectionField => ProjectCorrectionField::from($field),
            array_values($data['allowed_fields'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function correctionAttributesFromData(array $data): array
    {
        return array_intersect_key($data, array_flip(ProjectCorrectionField::editableProjectColumns()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<Department>
     */
    private static function departmentsFromData(array $data): array
    {
        return Department::query()
            ->whereIn('id', array_values($data['department_ids'] ?? []))
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function departmentFromData(array $data): Department
    {
        return Department::query()->findOrFail((int) $data['department_id']);
    }

    private static function appealFromProject(Project $project): ProjectAppeal
    {
        $appeal = $project->appeal()->first();

        if (! $appeal instanceof ProjectAppeal) {
            Log::warning('project.appeal.admin.rejected_missing_appeal', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Nie znaleziono odwołania dla projektu.');
        }

        return $appeal;
    }

    private static function returnableVerification(
        Project $project,
        Department $department,
        VerificationAssignmentType $type,
    ): InitialMeritVerification|FinalMeritVerification|ConsultationVerification {
        $query = match ($type) {
            VerificationAssignmentType::MeritInitial => InitialMeritVerification::query(),
            VerificationAssignmentType::MeritFinish => FinalMeritVerification::query(),
            VerificationAssignmentType::Consultation => ConsultationVerification::query(),
            default => null,
        };

        if ($query === null) {
            Log::warning('verification.card.return.rejected_invalid_type', [
                'project_id' => $project->id,
                'department_id' => $department->id,
                'type' => $type->value,
            ]);

            throw new DomainException('Nieobsługiwany typ karty weryfikacji.');
        }

        $verification = $query
            ->where('project_id', $project->id)
            ->where('department_id', $department->id)
            ->where('status', VerificationCardStatus::Sent->value)
            ->latest()
            ->first();

        if ($verification instanceof InitialMeritVerification
            || $verification instanceof FinalMeritVerification
            || $verification instanceof ConsultationVerification) {
            return $verification;
        }

        Log::warning('verification.card.return.rejected_missing_card', [
            'project_id' => $project->id,
            'department_id' => $department->id,
            'type' => $type->value,
        ]);

        throw new DomainException('Nie znaleziono wysłanej karty weryfikacji do cofnięcia.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{legacy: string, label: string, comments?: bool}>  $answerFields
     * @param  array<string, array{legacy: string, label: string, comments?: bool}>  $textFields
     * @return array<string, int|string|null>
     */
    private static function meritAnswersFromData(array $data, array $answerFields = [], array $textFields = []): array
    {
        $answers = [
            'notes' => $data['answers_notes'] ?? null,
        ];

        foreach ($answerFields as $fieldName => $definition) {
            if (array_key_exists($fieldName, $data) && $data[$fieldName] !== null && $data[$fieldName] !== '') {
                $answers[$definition['legacy']] = (int) $data[$fieldName];
            }

            $comment = trim((string) ($data[$fieldName.'_comments'] ?? ''));
            if ($comment !== '') {
                $answers[$definition['legacy'].'Comments'] = $comment;
            }
        }

        foreach ($textFields as $fieldName => $definition) {
            $value = trim((string) ($data[$fieldName] ?? ''));
            if ($value !== '') {
                $answers[$definition['legacy']] = $value;
            }

            if (($definition['comments'] ?? false) === true) {
                $comment = trim((string) ($data[$fieldName.'_comments'] ?? ''));
                if ($comment !== '') {
                    $answers[$definition['legacy'].'Comments'] = $comment;
                }
            }
        }

        return $answers;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{description: string, sum: int|float|string}>
     */
    private static function costRowsFromData(array $data, string $prefix): array
    {
        $rows = $data[$prefix.'_costs'] ?? null;

        if (is_array($rows)) {
            return self::costRowsFromRepeater($rows);
        }

        $description = trim((string) ($data[$prefix.'_cost_description'] ?? ''));
        $sum = $data[$prefix.'_cost_sum'] ?? null;

        if ($description === '' && ($sum === null || $sum === '')) {
            return [];
        }

        return [[
            'description' => $description,
            'sum' => $sum ?? '',
        ]];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array{description: string, sum: int|float|string}>
     */
    private static function costRowsFromRepeater(array $rows): array
    {
        $costs = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $description = trim((string) ($row['description'] ?? ''));
            $sum = $row['sum'] ?? null;

            if ($description === '' && ($sum === null || $sum === '')) {
                continue;
            }

            $costs[] = [
                'description' => $description,
                'sum' => $sum ?? '',
            ];
        }

        return $costs;
    }

    private static function optionalDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * @return list<string>
     */
    private static function projectFlags(Project $project): array
    {
        return [
            'Widoczność: '.($project->is_hidden ? 'ukryty publicznie' : 'widoczny zgodnie ze statusem'),
            'Lista poparcia: '.($project->is_support_list ? 'potwierdzona' : 'brak potwierdzenia'),
            'Korekta: '.($project->need_correction ? 'wymagana' : 'brak aktywnej korekty'),
            'Ponowna weryfikacja: '.($project->reverify ? 'tak' : 'nie'),
        ];
    }

    private static function projectTypeLabel(Project $project): string
    {
        return match ((int) $project->local) {
            1 => 'Projekt lokalny',
            2 => 'Projekt Zielonego BO',
            default => 'Projekt miejski',
        };
    }

    /**
     * @return list<string>
     */
    private static function authorSummary(Project $project): array
    {
        $author = $project->authors ?? [];
        $name = trim((string) data_get($author, 'first_name').' '.(string) data_get($author, 'last_name'));
        $address = trim(implode(' ', array_filter([
            data_get($author, 'street'),
            data_get($author, 'house_no'),
            data_get($author, 'flat_no') ? '/'.data_get($author, 'flat_no') : null,
            data_get($author, 'post_code'),
            data_get($author, 'city'),
        ])));

        return array_values(array_filter([
            'Imię i nazwisko: '.($name !== '' ? $name : 'nie podano'),
            'E-mail: '.(data_get($author, 'email') ?: $project->creator?->email ?: 'nie podano'),
            'Telefon: '.(data_get($author, 'phone') ?: 'nie podano'),
            'Adres: '.($address !== '' ? $address : 'nie podano'),
            'Kontakt publiczny: '.($project->contact_with ? 'tak' : 'nie'),
        ]));
    }

    /**
     * @return list<string>
     */
    private static function coauthorSummary(Project $project): array
    {
        $coauthors = $project->coauthors()->oldest()->get();

        if ($coauthors->isEmpty()) {
            return ['Brak współautorów.'];
        }

        return $coauthors
            ->map(function ($coauthor): string {
                $name = trim($coauthor->first_name.' '.$coauthor->last_name) ?: 'Współautor';
                $email = $coauthor->email ?: 'brak e-maila';
                $confirmed = $coauthor->confirm ? 'potwierdzony' : 'niepotwierdzony';

                return "{$name}, {$email}, {$confirmed}";
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{description: string, amount: string}>
     */
    private static function costItemsSummary(Project $project): array
    {
        $items = $project->costItems()->oldest()->get();

        if ($items->isEmpty()) {
            return [[
                'description' => 'Brak pozycji kosztorysu.',
                'amount' => '0,00 zł',
            ]];
        }

        return $items
            ->map(fn ($item): array => [
                'description' => $item->description,
                'amount' => self::money($item->amount),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{type: string, name: string, privacy: string}>
     */
    private static function filesSummary(Project $project): array
    {
        $files = $project->files()->oldest()->get();

        if ($files->isEmpty()) {
            return [[
                'type' => 'Brak',
                'name' => 'Nie dodano załączników.',
                'privacy' => '-',
            ]];
        }

        return $files
            ->map(fn ($file): array => [
                'type' => $file->type?->label() ?? 'Załącznik',
                'name' => $file->original_name,
                'privacy' => $file->is_private ? 'prywatny' : 'publiczny',
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function formalVerificationSummary(Project $project): array
    {
        $verifications = $project->formalVerifications()
            ->with(['createdBy', 'modifiedBy'])
            ->latest()
            ->get();

        if ($verifications->isEmpty()) {
            return ['Brak zapisanej karty weryfikacji formalnej.'];
        }

        return $verifications
            ->flatMap(function (FormalVerification $verification): array {
                return [
                    self::verificationHeader(
                        'Formalna',
                        self::projectStatusLabel($verification->status),
                        $verification->result,
                        $verification->createdBy?->name,
                        $verification->updated_at,
                        $verification->result_comments,
                    ),
                    ...self::answersSummary($verification->answers ?? [], self::formalAnswerLabelMap()),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function verificationAssignmentsSummary(Project $project): array
    {
        $assignments = $project->verificationAssignments()
            ->with('department')
            ->oldest()
            ->get();

        if ($assignments->isEmpty()) {
            return ['Brak przydzielonych jednostek.'];
        }

        return $assignments
            ->map(function (VerificationAssignment $assignment): string {
                $parts = [
                    self::assignmentTypeLabel($assignment->type).' - '.($assignment->department?->name ?? 'brak jednostki'),
                    'termin: '.self::dateTime($assignment->deadline),
                    'wysłano: '.self::dateTime($assignment->sent_at),
                    'status: '.($assignment->is_returned ? 'cofnięta' : 'aktywna'),
                ];

                if (filled($assignment->notes)) {
                    $parts[] = 'uwagi: '.$assignment->notes;
                }

                return implode(', ', $parts);
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function meritVerificationSummary(Project $project, string $type): array
    {
        [$relation, $labelMap, $label] = match ($type) {
            'initial' => ['initialMeritVerifications', self::meritAnswerLabelMap('initial'), 'Wstępna'],
            'final' => ['finalMeritVerifications', self::meritAnswerLabelMap('final'), 'Merytoryczna'],
            'consultation' => ['consultationVerifications', self::meritAnswerLabelMap('consultation'), 'Konsultacja'],
        };

        $verifications = $project->{$relation}()
            ->with(['department', 'createdBy'])
            ->latest()
            ->get();

        if ($verifications->isEmpty()) {
            return ["Brak zapisanej karty: {$label}."];
        }

        return $verifications
            ->flatMap(function ($verification) use ($label, $labelMap): array {
                return [
                    self::verificationHeader(
                        $label.' - '.($verification->department?->name ?? 'brak jednostki'),
                        self::cardStatusLabel($verification->status),
                        $verification->result,
                        $verification->createdBy?->name,
                        $verification->updated_at,
                        $verification->result_comments,
                    ),
                    ...self::answersSummary($verification->answers ?? [], $labelMap),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function boardVotesSummary(Project $project): array
    {
        $votes = $project->boardVotes()
            ->with('user')
            ->oldest()
            ->get();

        if ($votes->isEmpty()) {
            return ['Brak głosowań zespołów/komisji dla projektu.'];
        }

        return $votes
            ->map(fn ($vote): string => implode(', ', array_filter([
                'Rada/komisja: '.$vote->board_type->value,
                'głos: '.self::boardChoiceLabel($vote->board_type, (int) $vote->choice),
                'osoba: '.($vote->user?->name ?? 'nieznana'),
                'data: '.self::dateTime($vote->created_at),
                filled($vote->comment) ? 'komentarz: '.$vote->comment : null,
            ])))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function appealSummary(Project $project): array
    {
        $appeal = $project->appeal()->first();

        if ($appeal === null) {
            return ['Brak odwołania.'];
        }

        return array_values(array_filter([
            'Treść: '.$appeal->appeal_message,
            'Decyzja wstępna: '.self::appealDecisionLabel((int) $appeal->first_decision),
            'Data decyzji: '.self::dateTime($appeal->first_decision_created_at),
            filled($appeal->response_to_appeal) ? 'Odpowiedź: '.$appeal->response_to_appeal : null,
            'Data odpowiedzi: '.self::dateTime($appeal->response_created_at),
        ]));
    }

    /**
     * @return list<string>
     */
    private static function verificationVersionsSummary(Project $project): array
    {
        $verificationIds = collect([
            ...$project->formalVerifications()->pluck('id')->all(),
            ...$project->initialMeritVerifications()->pluck('id')->all(),
            ...$project->finalMeritVerifications()->pluck('id')->all(),
            ...$project->consultationVerifications()->pluck('id')->all(),
        ])->filter()->values();

        if ($verificationIds->isEmpty()) {
            return ['Brak wersji kart weryfikacji.'];
        }

        $versions = VerificationVersion::query()
            ->with('user')
            ->whereIn('verification_legacy_id', $verificationIds)
            ->latest()
            ->get();

        if ($versions->isEmpty()) {
            return ['Brak wersji kart weryfikacji.'];
        }

        return $versions
            ->map(fn (VerificationVersion $version): string => implode(', ', [
                self::assignmentTypeLabel(VerificationAssignmentType::tryFrom((int) $version->type)),
                'wersja z '.$version->created_at?->format('Y-m-d H:i'),
                'autor: '.($version->user?->name ?? 'system'),
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  array<string, string>  $labels
     * @return list<string>
     */
    private static function answersSummary(array $answers, array $labels): array
    {
        if ($answers === []) {
            return ['Brak odpowiedzi szczegółowych.'];
        }

        return collect($answers)
            ->map(function (mixed $value, string $key) use ($labels): string {
                $label = $labels[$key] ?? $key;
                $answer = match (true) {
                    is_bool($value) => $value ? 'tak' : 'nie',
                    $value === 1 => 'tak',
                    $value === 0 => 'nie',
                    $value === null => 'nie podano',
                    default => (string) $value,
                };

                return "{$label}: {$answer}";
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function formalAnswerLabelMap(): array
    {
        $labels = [];

        foreach (self::formalAnswerFields() as $definition) {
            $legacy = $definition['legacy'];
            $labels[$legacy] = $definition['label'];
            $labels[$legacy.'Comments'] = $definition['label'].' - uwagi';
        }

        $labels['isProjectCategory'] = 'Kategoria weryfikowanego projektu';

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    private static function meritAnswerLabelMap(string $type): array
    {
        $definitions = match ($type) {
            'initial' => [
                ...self::INITIAL_MERIT_ANSWER_FIELDS,
                ...self::INITIAL_MERIT_TEXT_FIELDS,
            ],
            'final' => [
                ...self::FINAL_MERIT_ANSWER_FIELDS,
                ...self::FINAL_MERIT_TEXT_FIELDS,
            ],
            'consultation' => self::CONSULTATION_TEXT_FIELDS,
        };

        $labels = [];

        foreach ($definitions as $definition) {
            $legacy = $definition['legacy'];
            $labels[$legacy] = $definition['label'];
            $labels[$legacy.'Comments'] = $definition['label'].' - uwagi';
        }

        return $labels;
    }

    private static function verificationHeader(
        string $name,
        string $status,
        ?bool $result,
        ?string $actor,
        ?Carbon $updatedAt,
        ?string $comments,
    ): string {
        return implode(', ', array_filter([
            $name,
            'status: '.$status,
            'wynik: '.self::resultLabel($result),
            'osoba: '.($actor ?: 'nie podano'),
            'aktualizacja: '.self::dateTime($updatedAt),
            filled($comments) ? 'uzasadnienie: '.$comments : null,
        ]));
    }

    private static function projectStatusLabel(int|string|null $status): string
    {
        return ProjectStatus::tryFrom((int) $status)?->adminLabel() ?? 'nieznany status';
    }

    private static function cardStatusLabel(mixed $status): string
    {
        $status = $status instanceof VerificationCardStatus ? $status : VerificationCardStatus::tryFrom((int) $status);

        return match ($status) {
            VerificationCardStatus::WorkingCopy => 'kopia robocza',
            VerificationCardStatus::Sent => 'wysłana',
            default => 'nieznany status',
        };
    }

    private static function assignmentTypeLabel(?VerificationAssignmentType $type): string
    {
        return match ($type) {
            VerificationAssignmentType::MeritInitial => 'Weryfikacja wstępna',
            VerificationAssignmentType::MeritFinish => 'Weryfikacja końcowa',
            VerificationAssignmentType::Consultation => 'Konsultacja',
            VerificationAssignmentType::FormalVerification => 'Weryfikacja formalna',
            default => 'Nieznany typ',
        };
    }

    private static function boardChoiceLabel(BoardType $boardType, int $choice): string
    {
        return self::boardVoteChoiceOptions($boardType)[$choice] ?? 'nieznany głos';
    }

    private static function appealDecisionLabel(int $decision): string
    {
        return match (ProjectAppealFirstDecision::tryFrom($decision)) {
            ProjectAppealFirstDecision::Pending => 'oczekuje',
            ProjectAppealFirstDecision::Rejected => 'odrzucone',
            ProjectAppealFirstDecision::Accepted => 'uwzględnione',
            default => 'nieznana decyzja',
        };
    }

    private static function resultLabel(?bool $result): string
    {
        return match ($result) {
            true => 'pozytywny',
            false => 'negatywny',
            null => 'nie podano',
        };
    }

    private static function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ').' zł';
    }

    private static function dateTime(mixed $dateTime): string
    {
        if ($dateTime instanceof Carbon) {
            return $dateTime->format('Y-m-d H:i');
        }

        if ($dateTime === null || $dateTime === '') {
            return 'nie podano';
        }

        return Carbon::parse($dateTime)->format('Y-m-d H:i');
    }

    private static function processStageLabel(Project $project): string
    {
        return match (true) {
            $project->status === ProjectStatus::WorkingCopy => 'Kopia robocza po stronie mieszkańca',
            $project->status === ProjectStatus::Submitted => 'Złożony, oczekuje na obsługę BDO',
            in_array($project->status, [ProjectStatus::DuringFormalVerification, ProjectStatus::FormallyVerified, ProjectStatus::RejectedFormally], true) => 'Weryfikacja formalna',
            in_array($project->status, [ProjectStatus::DuringInitialVerification, ProjectStatus::InitialVerificationRejected], true) => 'Weryfikacja wstępna',
            in_array($project->status, [ProjectStatus::SentForMeritVerification, ProjectStatus::DuringMeritVerification, ProjectStatus::MeritVerificationAccepted, ProjectStatus::MeritVerificationRejected], true) => 'Weryfikacja merytoryczna',
            in_array($project->status, [ProjectStatus::DuringTeamVerification, ProjectStatus::TeamAccepted, ProjectStatus::TeamRejected, ProjectStatus::TeamRejectedWithRecall], true) => 'Decyzje rady/komisji',
            $project->status === ProjectStatus::Picked => 'Lista do głosowania',
            $project->status === ProjectStatus::PickedForRealization => 'Wybrany do realizacji',
            $project->status->isRejected() => 'Odrzucony',
            default => 'Etap pośredni',
        };
    }

    private static function nextStepLabel(Project $project): string
    {
        return match ($project->status) {
            ProjectStatus::WorkingCopy => 'Mieszkaniec musi wysłać projekt do urzędu.',
            ProjectStatus::Submitted => 'Rozpocząć albo zakończyć weryfikację formalną.',
            ProjectStatus::DuringFormalVerification => $project->need_correction
                ? 'Oczekiwać na korektę mieszkańca albo ją zastosować.'
                : 'Zakończyć formalnie pozytywnie, odrzucić albo wezwać do korekty.',
            ProjectStatus::FormallyVerified => 'Przekazać do weryfikacji wstępnej.',
            ProjectStatus::DuringInitialVerification => 'Zebrać karty wstępne i skierować do dalszej weryfikacji.',
            ProjectStatus::SentForMeritVerification, ProjectStatus::DuringMeritVerification => 'Zebrać karty merytoryczne i konsultacje.',
            ProjectStatus::MeritVerificationAccepted => 'Przekazać do decyzji rady/komisji.',
            ProjectStatus::TeamAccepted => 'Projekt może trafić na listę do głosowania.',
            ProjectStatus::Picked => 'Projekt jest dostępny do głosowania.',
            ProjectStatus::PickedForRealization => 'Projekt jest na etapie realizacji.',
            default => $project->status->isRejected() ? 'Brak dalszych standardowych działań poza odwołaniem/retrybem.' : 'Sprawdzić historię weryfikacji i dostępne akcje.',
        };
    }

    /**
     * @return list<string>
     */
    private static function verificationProgress(Project $project): array
    {
        return [
            'Formalna: '.self::latestVerificationState($project, 'formalVerifications'),
            'Wstępna: '.self::latestVerificationState($project, 'initialMeritVerifications'),
            'Merytoryczna: '.self::latestVerificationState($project, 'finalMeritVerifications'),
            'Konsultacje: '.self::latestVerificationState($project, 'consultationVerifications'),
        ];
    }

    /**
     * @return list<string>
     */
    private static function votingProgress(Project $project): array
    {
        return [
            'Status listy: '.$project->status->adminLabel(),
            'Głosy rady/komisji: '.$project->boardVotes()->count(),
            'Odwołanie: '.($project->appeal()->exists() ? 'zarejestrowane' : 'brak'),
            'Wybrany do głosowania: '.($project->status === ProjectStatus::Picked ? 'tak' : 'nie'),
        ];
    }

    private static function latestVerificationState(Project $project, string $relation): string
    {
        $verification = $project->{$relation}()->latest()->first();

        if ($verification === null) {
            return 'brak karty';
        }

        if ($relation === 'formalVerifications') {
            return self::resultLabel($verification->result).' / '.self::projectStatusLabel($verification->status);
        }

        return self::resultLabel($verification->result).' / '.self::cardStatusLabel($verification->status);
    }

    private static function canVerifyProjects(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can(SystemPermission::ProjectsVerify->value)
                || $user->can(SystemPermission::ProjectsManage->value)
                || $user->hasAnyRole(['admin', 'bdo']));
    }

    private static function canManageProjects(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can(SystemPermission::ProjectsManage->value) || $user->hasAnyRole(['admin', 'bdo']));
    }

    private static function canManageFormalVerification(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can(SystemPermission::FormalVerificationManage->value) || self::canVerifyProjects());
    }

    private static function canManageMeritVerification(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can(SystemPermission::MeritVerificationManage->value) || self::canVerifyProjects());
    }

    private static function canManageProjectCorrections(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can(SystemPermission::ProjectCorrectionsManage->value) || self::canManageProjects());
    }

    private static function authenticatedUser(string $rejectionLog): User
    {
        $user = Auth::user();

        if ($user instanceof User) {
            return $user;
        }

        Log::warning($rejectionLog);

        throw new DomainException('Użytkownik musi być zalogowany.');
    }
}

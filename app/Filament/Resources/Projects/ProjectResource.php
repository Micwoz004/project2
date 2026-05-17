<?php

namespace App\Filament\Resources\Projects;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Actions\ApplyCorrectionAction;
use App\Domain\Projects\Actions\StartCorrectionAction;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Actions\AssignVerificationDepartmentAction;
use App\Domain\Verification\Actions\BeginFormalVerificationAction;
use App\Domain\Verification\Actions\CastProjectBoardVoteAction;
use App\Domain\Verification\Actions\CloseBoardVotingAction;
use App\Domain\Verification\Actions\CompleteFormalVerificationAction;
use App\Domain\Verification\Actions\ForwardFormalVerificationToInitialVerificationAction;
use App\Domain\Verification\Actions\RequestFormalCorrectionAction;
use App\Domain\Verification\Actions\RestartBoardVotingAction;
use App\Domain\Verification\Actions\SubmitConsultationVerificationAction;
use App\Domain\Verification\Actions\SubmitFinalMeritVerificationAction;
use App\Domain\Verification\Actions\SubmitInitialMeritVerificationAction;
use App\Domain\Verification\Enums\AtVoteChoice;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\OtVoteChoice;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Enums\ZkVoteChoice;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\FormalVerification;
use App\Domain\Verification\Models\InitialMeritVerification;
use App\Domain\Verification\Models\VerificationAssignment;
use App\Domain\Verification\Services\VerificationOverviewService;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
     * @var array<string, array{legacy: string, label: string}>
     */
    private const FORMAL_ANSWER_FIELDS = [
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
            'label' => 'Czy projekt zawiera dane kontaktowe do autora i współautorów?',
        ],
        'has_proper_attachments' => [
            'legacy' => 'hasProperAttachments',
            'label' => 'Czy załączono niezbędne załączniki i czy zostały zanonimizowane?',
        ],
        'has_support_attachment' => [
            'legacy' => 'hasSupportAttachment',
            'label' => 'Czy załączona została lista poparcia?',
        ],
        'is_data_correct' => [
            'legacy' => 'isDataCorrect',
            'label' => 'Czy projekt został wypełniony prawidłowo?',
        ],
        'is_description_valid' => [
            'legacy' => 'isDescriptionValid',
            'label' => 'Czy opis projektu jest jasny, konkretny i jednoznaczny?',
        ],
        'is_free_of_charge' => [
            'legacy' => 'isFreeOfCharge',
            'label' => 'Czy autor zawarł informacje o ogólnodostępności i nieodpłatności?',
        ],
        'is_correctly_assigned' => [
            'legacy' => 'isCorrectlyAssigned',
            'label' => 'Czy projekt przyporządkowano do odpowiedniej kategorii i obszaru?',
        ],
        'is_map_correct' => [
            'legacy' => 'isMapCorrect',
            'label' => 'Czy autor prawidłowo wskazał lokalizację projektu?',
        ],
        'has_required_consent' => [
            'legacy' => 'hasRequiredConsent',
            'label' => 'Czy złożono wszystkie wymagane oświadczenia?',
        ],
        'is_description_fair' => [
            'legacy' => 'isDescriptionFair',
            'label' => 'Czy opis nie wskazuje potencjalnego wykonawcy lub dostawcy?',
        ],
        'is_in_budget' => [
            'legacy' => 'isInBudget',
            'label' => 'Czy wartość projektu mieści się w puli środków?',
        ],
        'is_located_within_city' => [
            'legacy' => 'isLocatedWithinCity',
            'label' => 'Czy projekt jest zlokalizowany w granicach administracyjnych miasta?',
        ],
        'is_in_own_tasks' => [
            'legacy' => 'isInOwnTasks',
            'label' => 'Czy projekt mieści się w zadaniach własnych Gminy?',
        ],
    ];

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number_drawn')
                    ->label('Nr')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(80),
                TextColumn::make('area.name')
                    ->label('Obszar')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->adminLabel())
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
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
                self::verificationOverviewAction(),
                self::castBoardVoteAction(BoardType::Zk),
                self::castBoardVoteAction(BoardType::Ot),
                self::castBoardVoteAction(BoardType::At),
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
            self::meritAnswersFromData($data),
            $data['result_comments'] ?? null,
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
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
                TextInput::make('corrected_cost_description')
                    ->label('Opis kosztu szacunkowego'),
                TextInput::make('corrected_cost_sum')
                    ->label('Koszt szacunkowy')
                    ->numeric(),
                TextInput::make('future_cost_description')
                    ->label('Opis kosztu przyszłego'),
                TextInput::make('future_cost_sum')
                    ->label('Koszt przyszły')
                    ->numeric(),
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

    private static function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private static function formalVerificationAnswerSchema(): array
    {
        $schema = [];

        foreach (self::FORMAL_ANSWER_FIELDS as $fieldName => $definition) {
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

        foreach (self::FORMAL_ANSWER_FIELDS as $fieldName => $definition) {
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

    private static function optionalDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
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

<?php

namespace App\Filament\Resources\Projects;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Actions\BeginFormalVerificationAction;
use App\Domain\Verification\Actions\CastProjectBoardVoteAction;
use App\Domain\Verification\Actions\CloseBoardVotingAction;
use App\Domain\Verification\Actions\CompleteFormalVerificationAction;
use App\Domain\Verification\Actions\ForwardFormalVerificationToInitialVerificationAction;
use App\Domain\Verification\Actions\RequestFormalCorrectionAction;
use App\Domain\Verification\Actions\RestartBoardVotingAction;
use App\Domain\Verification\Enums\AtVoteChoice;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\OtVoteChoice;
use App\Domain\Verification\Enums\ZkVoteChoice;
use App\Domain\Verification\Models\FormalVerification;
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
        return self::canVerifyProjects()
            && $project->status === ProjectStatus::Submitted;
    }

    public static function canCompleteFormalVerification(Project $project): bool
    {
        return self::canVerifyProjects()
            && in_array($project->status, [
                ProjectStatus::Submitted,
                ProjectStatus::DuringFormalVerification,
            ], true);
    }

    public static function canRequestFormalCorrection(Project $project): bool
    {
        return self::canVerifyProjects()
            && in_array($project->status, [
                ProjectStatus::Submitted,
                ProjectStatus::DuringFormalVerification,
            ], true);
    }

    public static function canForwardFormalVerification(Project $project): bool
    {
        return self::canVerifyProjects()
            && $project->status === ProjectStatus::FormallyVerified;
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
    private static function formalVerificationAnswerSchema(): array
    {
        return [
            Toggle::make('was_sent_on_correct_form')
                ->label('Zgłoszenie na właściwym formularzu'),
            Toggle::make('has_support_attachment')
                ->label('Poprawna lista poparcia'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int>
     */
    private static function formalAnswersFromData(array $data): array
    {
        return [
            'wasSentOnCorrectForm' => (bool) ($data['was_sent_on_correct_form'] ?? false) ? 1 : 0,
            'hasSupportAttachment' => (bool) ($data['has_support_attachment'] ?? false) ? 1 : 0,
        ];
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
     * @return list<Department>
     */
    private static function departmentsFromData(array $data): array
    {
        return Department::query()
            ->whereIn('id', array_values($data['department_ids'] ?? []))
            ->get()
            ->all();
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
            && ($user->can('projects.verify') || $user->can('projects.manage') || $user->hasAnyRole(['admin', 'bdo']));
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

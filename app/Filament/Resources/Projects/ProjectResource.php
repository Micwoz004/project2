<?php

namespace App\Filament\Resources\Projects;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Verification\Actions\CloseBoardVotingAction;
use App\Domain\Verification\Actions\RestartBoardVotingAction;
use App\Domain\Verification\Enums\BoardType;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

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
                self::closeBoardVotingAction(BoardType::Ot),
                self::restartBoardVotingAction(BoardType::Ot),
                self::closeBoardVotingAction(BoardType::At),
                self::restartBoardVotingAction(BoardType::At),
                DeleteAction::make(),
            ]);
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
}

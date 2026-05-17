<?php

namespace App\Filament\Resources\ProjectChangeSuggestions;

use App\Domain\Projects\Actions\DecideProjectChangeSuggestionAction;
use App\Domain\Projects\Enums\ProjectChangeSuggestionDecision;
use App\Domain\Projects\Models\ProjectChangeSuggestion;
use App\Filament\Resources\ProjectChangeSuggestions\Pages\ListProjectChangeSuggestions;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class ProjectChangeSuggestionResource extends Resource
{
    protected static ?string $model = ProjectChangeSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Projekty';

    protected static ?int $navigationSort = 45;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return 'propozycja zmian';
    }

    public static function getPluralModelLabel(): string
    {
        return 'propozycje zmian';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('project.number')
                    ->label('Nr projektu')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('project.title')
                    ->label('Projekt')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('decision')
                    ->label('Decyzja')
                    ->formatStateUsing(fn (?ProjectChangeSuggestionDecision $state): string => self::decisionLabel($state ?? ProjectChangeSuggestionDecision::Pending))
                    ->badge(),
                IconColumn::make('is_accepted_by_admin')
                    ->label('Decyzja admin.')
                    ->boolean(),
                TextColumn::make('deadline')
                    ->label('Termin')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('decision_at')
                    ->label('Rozstrzygnięto')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                self::acceptAction(),
                self::declineAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectChangeSuggestions::route('/'),
        ];
    }

    public static function acceptFromAdmin(ProjectChangeSuggestion $suggestion): ProjectChangeSuggestion
    {
        return self::decideFromAdmin($suggestion, ProjectChangeSuggestionDecision::Accepted);
    }

    public static function declineFromAdmin(ProjectChangeSuggestion $suggestion): ProjectChangeSuggestion
    {
        return self::decideFromAdmin($suggestion, ProjectChangeSuggestionDecision::Declined);
    }

    private static function acceptAction(): Action
    {
        return Action::make('accept')
            ->label('Akceptuj')
            ->requiresConfirmation()
            ->visible(fn (ProjectChangeSuggestion $record): bool => self::isPending($record))
            ->action(fn (ProjectChangeSuggestion $record): ProjectChangeSuggestion => self::acceptFromAdmin($record));
    }

    private static function declineAction(): Action
    {
        return Action::make('decline')
            ->label('Odrzuć')
            ->requiresConfirmation()
            ->visible(fn (ProjectChangeSuggestion $record): bool => self::isPending($record))
            ->action(fn (ProjectChangeSuggestion $record): ProjectChangeSuggestion => self::declineFromAdmin($record));
    }

    private static function decideFromAdmin(
        ProjectChangeSuggestion $suggestion,
        ProjectChangeSuggestionDecision $decision,
    ): ProjectChangeSuggestion {
        $operator = Auth::user();

        if (! $operator instanceof User) {
            Log::warning('project.change_suggestion.admin_decide.rejected_guest', [
                'suggestion_id' => $suggestion->id,
            ]);

            throw new DomainException('Użytkownik musi być zalogowany.');
        }

        return app(DecideProjectChangeSuggestionAction::class)->execute($suggestion, $decision, $operator);
    }

    private static function isPending(ProjectChangeSuggestion $suggestion): bool
    {
        return ($suggestion->decision ?? ProjectChangeSuggestionDecision::Pending) === ProjectChangeSuggestionDecision::Pending;
    }

    private static function decisionLabel(ProjectChangeSuggestionDecision $decision): string
    {
        return match ($decision) {
            ProjectChangeSuggestionDecision::Pending => 'oczekuje',
            ProjectChangeSuggestionDecision::Declined => 'odrzucona',
            ProjectChangeSuggestionDecision::Accepted => 'zaakceptowana',
        };
    }
}

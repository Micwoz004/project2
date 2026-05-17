<?php

namespace App\Filament\Resources\ProjectAppeals;

use App\Domain\Verification\Enums\ProjectAppealFirstDecision;
use App\Domain\Verification\Models\ProjectAppeal;
use App\Filament\Resources\ProjectAppeals\Pages\ListProjectAppeals;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProjectAppealResource extends Resource
{
    protected static ?string $model = ProjectAppeal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Projekty';

    protected static ?int $navigationSort = 46;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return 'odwołanie';
    }

    public static function getPluralModelLabel(): string
    {
        return 'odwołania';
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
                TextColumn::make('appeal_message')
                    ->label('Odwołanie')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('first_decision')
                    ->label('Decyzja wstępna')
                    ->formatStateUsing(fn (?int $state): string => self::firstDecisionLabel($state))
                    ->badge(),
                IconColumn::make('response_to_appeal')
                    ->label('Odpowiedź')
                    ->boolean(),
                TextColumn::make('response_created_at')
                    ->label('Data odpowiedzi')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectAppeals::route('/'),
        ];
    }

    private static function firstDecisionLabel(?int $decision): string
    {
        return match ($decision ?? ProjectAppealFirstDecision::Pending->value) {
            ProjectAppealFirstDecision::Rejected->value => 'odrzucone',
            ProjectAppealFirstDecision::Accepted->value => 'przyjęte',
            default => 'oczekuje',
        };
    }
}

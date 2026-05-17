<?php

namespace App\Filament\Resources\ResultPublications;

use App\Domain\Results\Models\ResultPublication;
use App\Filament\Resources\ResultPublications\Pages\ListResultPublications;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ResultPublicationResource extends Resource
{
    protected static ?string $model = ResultPublication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Głosowanie';

    protected static ?int $navigationSort = 35;

    protected static ?string $recordTitleAttribute = 'version';

    public static function getModelLabel(): string
    {
        return 'snapshot wyników';
    }

    public static function getPluralModelLabel(): string
    {
        return 'snapshoty wyników';
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
                TextColumn::make('budget_edition_id')
                    ->label('Edycja')
                    ->sortable(),
                TextColumn::make('version')
                    ->label('Wersja')
                    ->sortable(),
                TextColumn::make('total_points')
                    ->label('Punkty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('projects_count')
                    ->label('Projekty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('publishedBy.name')
                    ->label('Operator')
                    ->placeholder('System'),
                TextColumn::make('published_at')
                    ->label('Utrwalono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResultPublications::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ProjectAreas;

use App\Domain\Projects\Models\ProjectArea;
use App\Filament\Resources\ProjectAreas\Pages\CreateProjectArea;
use App\Filament\Resources\ProjectAreas\Pages\EditProjectArea;
use App\Filament\Resources\ProjectAreas\Pages\ListProjectAreas;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectAreaResource extends Resource
{
    protected static ?string $model = ProjectArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'obszar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'obszary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nazwa')
                ->required()
                ->maxLength(64),
            TextInput::make('symbol')
                ->label('Symbol')
                ->required()
                ->maxLength(8),
            TextInput::make('name_shortcut')
                ->label('Skrót nazwy')
                ->maxLength(255),
            Toggle::make('is_local')
                ->label('Obszar lokalny')
                ->default(true),
            TextInput::make('cost_limit')
                ->label('Limit kosztu')
                ->numeric()
                ->default(0),
            TextInput::make('cost_limit_small')
                ->label('Limit mały')
                ->numeric()
                ->default(0),
            TextInput::make('cost_limit_big')
                ->label('Limit duży')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('symbol')
                    ->label('Symbol')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_local')
                    ->label('Lokalny')
                    ->boolean(),
                TextColumn::make('cost_limit_big')
                    ->label('Limit duży')
                    ->money('PLN')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectAreas::route('/'),
            'create' => CreateProjectArea::route('/create'),
            'edit' => EditProjectArea::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CostGuideItems;

use App\Domain\Settings\Models\CostGuideItem;
use App\Filament\Resources\CostGuideItems\Pages\CreateCostGuideItem;
use App\Filament\Resources\CostGuideItems\Pages\EditCostGuideItem;
use App\Filament\Resources\CostGuideItems\Pages\ListCostGuideItems;
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
use UnitEnum;

class CostGuideItemResource extends Resource
{
    protected static ?string $model = CostGuideItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Treści publiczne';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'label';

    public static function getModelLabel(): string
    {
        return 'pozycja cennika';
    }

    public static function getPluralModelLabel(): string
    {
        return 'cennik inspiracji';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label('Nazwa')
                ->required()
                ->maxLength(180),
            TextInput::make('price_range')
                ->label('Widełki cenowe')
                ->required()
                ->maxLength(120),
            TextInput::make('sort')
                ->label('Kolejność')
                ->numeric()
                ->default(0),
            Toggle::make('is_published')
                ->label('Opublikowana')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_range')
                    ->label('Widełki cenowe')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Publikacja')
                    ->boolean(),
                TextColumn::make('sort')
                    ->label('Kolejność')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCostGuideItems::route('/'),
            'create' => CreateCostGuideItem::route('/create'),
            'edit' => EditCostGuideItem::route('/{record}/edit'),
        ];
    }
}

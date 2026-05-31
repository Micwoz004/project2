<?php

namespace App\Products\EkoUslugi\Filament\Resources\WasteItems;

use App\Products\EkoUslugi\Domain\Waste\Models\WasteFraction;
use App\Products\EkoUslugi\Domain\Waste\Models\WasteItem;
use App\Products\EkoUslugi\Filament\Resources\WasteItems\Pages\CreateWasteItem;
use App\Products\EkoUslugi\Filament\Resources\WasteItems\Pages\EditWasteItem;
use App\Products\EkoUslugi\Filament\Resources\WasteItems\Pages\ListWasteItems;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WasteItemResource extends Resource
{
    protected static ?string $model = WasteItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Eko usługi';

    public static function getModelLabel(): string
    {
        return 'odpad';
    }

    public static function getPluralModelLabel(): string
    {
        return 'odpady';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nazwa')->required()->maxLength(150),
            Select::make('eko_waste_fraction_id')
                ->label('Frakcja')
                ->options(fn (): array => WasteFraction::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            Select::make('status')->label('Status')->options(['active' => 'Aktywny', 'inactive' => 'Nieaktywny'])->default('active')->required(),
            Toggle::make('goes_to_pszok')->label('Przyjmowany w PSZOK'),
            Textarea::make('instruction')->label('Instrukcja')->rows(5)->columnSpanFull(),
            Repeater::make('synonyms')
                ->label('Synonimy')
                ->relationship()
                ->schema([
                    TextInput::make('synonym')->label('Synonim')->required()->maxLength(150),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('fraction.name')->label('Frakcja')->sortable(),
                IconColumn::make('goes_to_pszok')->label('PSZOK')->boolean(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWasteItems::route('/'),
            'create' => CreateWasteItem::route('/create'),
            'edit' => EditWasteItem::route('/{record}/edit'),
        ];
    }
}

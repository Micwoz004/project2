<?php

namespace App\Products\EkoUslugi\Filament\Resources\WasteFractions;

use App\Products\EkoUslugi\Domain\Waste\Models\WasteFraction;
use App\Products\EkoUslugi\Filament\Resources\WasteFractions\Pages\CreateWasteFraction;
use App\Products\EkoUslugi\Filament\Resources\WasteFractions\Pages\EditWasteFraction;
use App\Products\EkoUslugi\Filament\Resources\WasteFractions\Pages\ListWasteFractions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WasteFractionResource extends Resource
{
    protected static ?string $model = WasteFraction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Eko usługi';

    public static function getModelLabel(): string
    {
        return 'frakcja odpadu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'frakcje odpadów';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nazwa')->required()->maxLength(150),
            ColorPicker::make('color')->label('Kolor'),
            TextInput::make('icon')->label('Ikona')->maxLength(80),
            Select::make('status')->label('Status')->options(['active' => 'Aktywna', 'inactive' => 'Nieaktywna'])->default('active')->required(),
            Textarea::make('description')->label('Opis')->columnSpanFull(),
            Textarea::make('what_to_put')->label('Co wrzucać')->rows(6)->columnSpanFull(),
            Textarea::make('what_not_to_put')->label('Czego nie wrzucać')->rows(6)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')->label('Kolor'),
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Aktualizacja')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWasteFractions::route('/'),
            'create' => CreateWasteFraction::route('/create'),
            'edit' => EditWasteFraction::route('/{record}/edit'),
        ];
    }
}

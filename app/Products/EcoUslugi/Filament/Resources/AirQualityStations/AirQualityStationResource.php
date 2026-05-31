<?php

namespace App\Products\EcoUslugi\Filament\Resources\AirQualityStations;

use App\Products\EcoUslugi\Domain\AirQuality\Models\AirQualityStation;
use App\Products\EcoUslugi\Filament\Resources\AirQualityStations\Pages\CreateAirQualityStation;
use App\Products\EcoUslugi\Filament\Resources\AirQualityStations\Pages\EditAirQualityStation;
use App\Products\EcoUslugi\Filament\Resources\AirQualityStations\Pages\ListAirQualityStations;
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

class AirQualityStationResource extends Resource
{
    protected static ?string $model = AirQualityStation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Ekousługi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('external_id')->label('ID GIOŚ')->maxLength(80),
            TextInput::make('name')->label('Nazwa')->required()->maxLength(180),
            TextInput::make('city')->label('Miasto')->maxLength(120),
            TextInput::make('street')->label('Ulica')->maxLength(180),
            TextInput::make('latitude')->label('Szerokość geogr.')->numeric(),
            TextInput::make('longitude')->label('Długość geogr.')->numeric(),
            Toggle::make('is_active')->label('Aktywna')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('external_id')->label('ID GIOŚ')->searchable(),
                TextColumn::make('city')->label('Miasto')->searchable(),
                IconColumn::make('is_active')->label('Aktywna')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAirQualityStations::route('/'),
            'create' => CreateAirQualityStation::route('/create'),
            'edit' => EditAirQualityStation::route('/{record}/edit'),
        ];
    }
}

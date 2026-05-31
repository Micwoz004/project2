<?php

namespace App\Products\EcoUslugi\Filament\Resources\PszokPoints;

use App\Products\EcoUslugi\Domain\Pszok\Models\PszokPoint;
use App\Products\EcoUslugi\Domain\Waste\Models\WasteFraction;
use App\Products\EcoUslugi\Filament\Resources\PszokPoints\Pages\CreatePszokPoint;
use App\Products\EcoUslugi\Filament\Resources\PszokPoints\Pages\EditPszokPoint;
use App\Products\EcoUslugi\Filament\Resources\PszokPoints\Pages\ListPszokPoints;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PszokPointResource extends Resource
{
    protected static ?string $model = PszokPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Ekousługi';

    public static function getModelLabel(): string
    {
        return 'punkt PSZOK';
    }

    public static function getPluralModelLabel(): string
    {
        return 'punkty PSZOK';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dane punktu')
                ->schema([
                    TextInput::make('name')->label('Nazwa')->required()->maxLength(180),
                    Select::make('status')
                        ->label('Status')
                        ->options(['active' => 'Aktywny', 'inactive' => 'Nieaktywny', 'temporarily_closed' => 'Czasowo zamknięty'])
                        ->default('active')
                        ->required(),
                    TextInput::make('phone')->label('Telefon')->maxLength(40),
                    TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                    Select::make('fractions')
                        ->label('Przyjmowane frakcje')
                        ->multiple()
                        ->relationship('fractions', 'name')
                        ->options(fn (): array => WasteFraction::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                    Textarea::make('description')->label('Opis')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Adres')
                ->schema([
                    TextInput::make('locality')->label('Miejscowość')->maxLength(150),
                    TextInput::make('street')->label('Ulica')->maxLength(180),
                    TextInput::make('building_number')->label('Numer')->maxLength(20),
                    TextInput::make('postal_code')->label('Kod pocztowy')->maxLength(12),
                    TextInput::make('latitude')->label('Szerokość geogr.')->numeric(),
                    TextInput::make('longitude')->label('Długość geogr.')->numeric(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('locality')->label('Miejscowość')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Aktualizacja')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPszokPoints::route('/'),
            'create' => CreatePszokPoint::route('/create'),
            'edit' => EditPszokPoint::route('/{record}/edit'),
        ];
    }
}

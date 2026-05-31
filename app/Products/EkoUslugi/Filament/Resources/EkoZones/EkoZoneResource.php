<?php

namespace App\Products\EkoUslugi\Filament\Resources\EkoZones;

use App\Products\EkoUslugi\Domain\Address\Models\EkoZone;
use App\Products\EkoUslugi\Filament\Resources\EkoZones\Pages\CreateEkoZone;
use App\Products\EkoUslugi\Filament\Resources\EkoZones\Pages\EditEkoZone;
use App\Products\EkoUslugi\Filament\Resources\EkoZones\Pages\ListEkoZones;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
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

class EkoZoneResource extends Resource
{
    protected static ?string $model = EkoZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Eko usługi';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'strefa odbioru';
    }

    public static function getPluralModelLabel(): string
    {
        return 'strefy odbioru';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Strefa')
                ->schema([
                    TextInput::make('code')->label('Kod')->required()->maxLength(50),
                    TextInput::make('name')->label('Nazwa')->required()->maxLength(150),
                    Select::make('building_type')
                        ->label('Typ zabudowy')
                        ->options([
                            'mixed' => 'Mieszana',
                            'single_family' => 'Jednorodzinna',
                            'multi_family' => 'Wielorodzinna',
                        ])
                        ->default('mixed')
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(['active' => 'Aktywna', 'inactive' => 'Nieaktywna'])
                        ->default('active')
                        ->required(),
                    Textarea::make('description')->label('Opis')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Reguły adresowe')
                ->schema([
                    Repeater::make('rules')
                        ->relationship()
                        ->schema([
                            TextInput::make('locality')->label('Miejscowość')->maxLength(150),
                            TextInput::make('street')->label('Ulica')->maxLength(180),
                            TextInput::make('exact_building_number')->label('Dokładny numer')->maxLength(20),
                            TextInput::make('building_from')->label('Numer od')->maxLength(20),
                            TextInput::make('building_to')->label('Numer do')->maxLength(20),
                            Select::make('parity')
                                ->label('Parzystość')
                                ->options(['all' => 'Wszystkie', 'even' => 'Parzyste', 'odd' => 'Nieparzyste'])
                                ->default('all')
                                ->required(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kod')->searchable()->sortable(),
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('building_type')->label('Typ zabudowy')->badge(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Aktualizacja')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEkoZones::route('/'),
            'create' => CreateEkoZone::route('/create'),
            'edit' => EditEkoZone::route('/{record}/edit'),
        ];
    }
}

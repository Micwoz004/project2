<?php

namespace App\Products\EcoUslugi\Filament\Resources\CollectionSchedules;

use App\Products\EcoUslugi\Domain\Address\Models\EcoZone;
use App\Products\EcoUslugi\Domain\Schedule\Models\CollectionSchedule;
use App\Products\EcoUslugi\Domain\Waste\Models\WasteFraction;
use App\Products\EcoUslugi\Filament\Resources\CollectionSchedules\Pages\CreateCollectionSchedule;
use App\Products\EcoUslugi\Filament\Resources\CollectionSchedules\Pages\EditCollectionSchedule;
use App\Products\EcoUslugi\Filament\Resources\CollectionSchedules\Pages\ListCollectionSchedules;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class CollectionScheduleResource extends Resource
{
    protected static ?string $model = CollectionSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Ekousługi';

    public static function getModelLabel(): string
    {
        return 'harmonogram odbioru';
    }

    public static function getPluralModelLabel(): string
    {
        return 'harmonogramy odbioru';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Harmonogram')
                ->schema([
                    TextInput::make('name')->label('Nazwa')->required()->maxLength(180),
                    Select::make('status')->label('Status')->options(['active' => 'Aktywny', 'inactive' => 'Nieaktywny'])->default('active')->required(),
                    DatePicker::make('valid_from')->label('Ważny od'),
                    DatePicker::make('valid_to')->label('Ważny do'),
                    Textarea::make('notes')->label('Notatki')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Terminy odbioru')
                ->schema([
                    Repeater::make('dates')
                        ->relationship()
                        ->schema([
                            Select::make('eco_zone_id')
                                ->label('Strefa')
                                ->options(fn (): array => EcoZone::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                            Select::make('eco_waste_fraction_id')
                                ->label('Frakcja')
                                ->options(fn (): array => WasteFraction::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                            DatePicker::make('collection_date')->label('Data odbioru')->required(),
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
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('valid_from')->label('Od')->date()->sortable(),
                TextColumn::make('valid_to')->label('Do')->date()->sortable(),
                TextColumn::make('dates_count')->counts('dates')->label('Terminów'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollectionSchedules::route('/'),
            'create' => CreateCollectionSchedule::route('/create'),
            'edit' => EditCollectionSchedule::route('/{record}/edit'),
        ];
    }
}

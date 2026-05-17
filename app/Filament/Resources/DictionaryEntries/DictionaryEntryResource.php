<?php

namespace App\Filament\Resources\DictionaryEntries;

use App\Domain\Dictionaries\Enums\DictionaryKind;
use App\Domain\Dictionaries\Models\DictionaryEntry;
use App\Filament\Resources\DictionaryEntries\Pages\CreateDictionaryEntry;
use App\Filament\Resources\DictionaryEntries\Pages\EditDictionaryEntry;
use App\Filament\Resources\DictionaryEntries\Pages\ListDictionaryEntries;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DictionaryEntryResource extends Resource
{
    protected static ?string $model = DictionaryEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Słowniki';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'value';

    public static function getModelLabel(): string
    {
        return 'pozycja słownika';
    }

    public static function getPluralModelLabel(): string
    {
        return 'słowniki legacy';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')
                ->label('Typ')
                ->options(self::kindOptions())
                ->required(),
            TextInput::make('value')
                ->label('Wartość')
                ->required()
                ->maxLength(255),
            Toggle::make('active')
                ->label('Aktywna')
                ->default(true),
            TextInput::make('source_table')
                ->label('Tabela legacy')
                ->maxLength(64),
            TextInput::make('legacy_id')
                ->label('Legacy ID')
                ->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kind')
                    ->label('Typ')
                    ->formatStateUsing(fn (DictionaryKind $state): string => self::kindLabel($state))
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Wartość')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Aktywna')
                    ->boolean(),
                TextColumn::make('source_table')
                    ->label('Źródło')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('kind')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDictionaryEntries::route('/'),
            'create' => CreateDictionaryEntry::route('/create'),
            'edit' => EditDictionaryEntry::route('/{record}/edit'),
        ];
    }

    private static function kindOptions(): array
    {
        $options = [];

        foreach (DictionaryKind::cases() as $kind) {
            $options[$kind->value] = self::kindLabel($kind);
        }

        return $options;
    }

    private static function kindLabel(DictionaryKind $kind): string
    {
        return match ($kind) {
            DictionaryKind::FirstName => 'Imię',
            DictionaryKind::LastName => 'Nazwisko',
            DictionaryKind::MotherLastName => 'Nazwisko matki',
        };
    }
}

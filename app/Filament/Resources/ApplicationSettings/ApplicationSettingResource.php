<?php

namespace App\Filament\Resources\ApplicationSettings;

use App\Domain\Settings\Models\ApplicationSetting;
use App\Filament\Resources\ApplicationSettings\Pages\CreateApplicationSetting;
use App\Filament\Resources\ApplicationSettings\Pages\EditApplicationSetting;
use App\Filament\Resources\ApplicationSettings\Pages\ListApplicationSettings;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ApplicationSettingResource extends Resource
{
    protected static ?string $model = ApplicationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Ustawienia';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'key';

    public static function getModelLabel(): string
    {
        return 'ustawienie aplikacji';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ustawienia aplikacji';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('category')
                ->label('Kategoria')
                ->required()
                ->maxLength(64),
            TextInput::make('key')
                ->label('Klucz')
                ->required()
                ->maxLength(255),
            Textarea::make('value')
                ->label('Wartość')
                ->rows(8)
                ->columnSpanFull(),
            TextInput::make('legacy_id')
                ->label('Legacy ID')
                ->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label('Kategoria')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->label('Klucz')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Wartość')
                    ->limit(80)
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('category')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplicationSettings::route('/'),
            'create' => CreateApplicationSetting::route('/create'),
            'edit' => EditApplicationSetting::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Products\EcoServices\Filament\Resources\NewsCategories;

use App\Products\EcoServices\Domain\News\Models\NewsCategory;
use App\Products\EcoServices\Filament\Resources\NewsCategories\Pages\CreateNewsCategory;
use App\Products\EcoServices\Filament\Resources\NewsCategories\Pages\EditNewsCategory;
use App\Products\EcoServices\Filament\Resources\NewsCategories\Pages\ListNewsCategories;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NewsCategoryResource extends Resource
{
    protected static ?string $model = NewsCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Ekousługi - aktualności';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nazwa')->required()->maxLength(150),
            TextInput::make('slug')->label('Slug')->required()->maxLength(180)->unique(ignoreRecord: true),
            Select::make('status')->label('Status')->options(['active' => 'Aktywna', 'inactive' => 'Nieaktywna'])->default('active')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsCategories::route('/'),
            'create' => CreateNewsCategory::route('/create'),
            'edit' => EditNewsCategory::route('/{record}/edit'),
        ];
    }
}

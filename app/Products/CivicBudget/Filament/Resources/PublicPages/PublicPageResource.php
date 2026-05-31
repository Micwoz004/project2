<?php

namespace App\Products\CivicBudget\Filament\Resources\PublicPages;

use App\Products\CivicBudget\Domain\Settings\Models\PublicPage;
use App\Products\CivicBudget\Filament\Resources\PublicPages\Pages\CreatePublicPage;
use App\Products\CivicBudget\Filament\Resources\PublicPages\Pages\EditPublicPage;
use App\Products\CivicBudget\Filament\Resources\PublicPages\Pages\ListPublicPages;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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

class PublicPageResource extends Resource
{
    protected static ?string $model = PublicPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Treści publiczne';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'strona informacyjna';
    }

    public static function getPluralModelLabel(): string
    {
        return 'strony informacyjne';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Tytuł')
                ->required()
                ->maxLength(180),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(180)
                ->unique(ignoreRecord: true),
            TextInput::make('sort')
                ->label('Kolejność')
                ->numeric()
                ->default(0),
            Toggle::make('is_published')
                ->label('Opublikowana')
                ->default(false),
            Textarea::make('body')
                ->label('Treść HTML')
                ->rows(16)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
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
            'index' => ListPublicPages::route('/'),
            'create' => CreatePublicPage::route('/create'),
            'edit' => EditPublicPage::route('/{record}/edit'),
        ];
    }
}

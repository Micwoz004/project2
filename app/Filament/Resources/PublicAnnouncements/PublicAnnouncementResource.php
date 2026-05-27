<?php

namespace App\Filament\Resources\PublicAnnouncements;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Settings\Models\PublicAnnouncement;
use App\Filament\Resources\PublicAnnouncements\Pages\CreatePublicAnnouncement;
use App\Filament\Resources\PublicAnnouncements\Pages\EditPublicAnnouncement;
use App\Filament\Resources\PublicAnnouncements\Pages\ListPublicAnnouncements;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class PublicAnnouncementResource extends Resource
{
    protected static ?string $model = PublicAnnouncement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Treści publiczne';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'ogłoszenie publiczne';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ogłoszenia publiczne';
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
            Select::make('budget_edition_id')
                ->label('Edycja')
                ->options(fn (): array => BudgetEdition::query()
                    ->orderByDesc('propose_start')
                    ->pluck('id', 'id')
                    ->all())
                ->searchable(),
            Textarea::make('lead')
                ->label('Lead')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label('Treść HTML')
                ->rows(14)
                ->required()
                ->columnSpanFull(),
            DateTimePicker::make('published_at')
                ->label('Data publikacji'),
            Toggle::make('is_published')
                ->label('Opublikowane')
                ->default(false),
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
                TextColumn::make('published_at')
                    ->label('Data publikacji')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicAnnouncements::route('/'),
            'create' => CreatePublicAnnouncement::route('/create'),
            'edit' => EditPublicAnnouncement::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Products\EcoServices\Filament\Resources\NewsPosts;

use App\Products\EcoServices\Domain\Address\Models\EcoZone;
use App\Products\EcoServices\Domain\News\Models\NewsCategory;
use App\Products\EcoServices\Domain\News\Models\NewsPost;
use App\Products\EcoServices\Filament\Resources\NewsPosts\Pages\CreateNewsPost;
use App\Products\EcoServices\Filament\Resources\NewsPosts\Pages\EditNewsPost;
use App\Products\EcoServices\Filament\Resources\NewsPosts\Pages\ListNewsPosts;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Ekousługi - aktualności';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Tytuł')->required()->maxLength(180),
            TextInput::make('slug')->label('Slug')->required()->maxLength(180)->unique(ignoreRecord: true),
            Select::make('eco_news_category_id')
                ->label('Kategoria')
                ->options(fn (): array => NewsCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            Select::make('status')->label('Status')->options(['draft' => 'Szkic', 'published' => 'Opublikowany', 'archived' => 'Archiwalny'])->default('draft')->required(),
            Select::make('scope_type')->label('Zakres')->options(['global' => 'Wszyscy', 'zone' => 'Strefa'])->default('global')->required(),
            Select::make('eco_zone_id')
                ->label('Strefa')
                ->options(fn (): array => EcoZone::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            DateTimePicker::make('published_at')->label('Data publikacji'),
            Textarea::make('lead')->label('Lead')->maxLength(500)->columnSpanFull(),
            Textarea::make('body')->label('Treść HTML')->required()->rows(14)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Tytuł')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategoria'),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('published_at')->label('Publikacja')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsPosts::route('/'),
            'create' => CreateNewsPost::route('/create'),
            'edit' => EditNewsPost::route('/{record}/edit'),
        ];
    }
}

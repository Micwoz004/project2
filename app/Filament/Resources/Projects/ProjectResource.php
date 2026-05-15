<?php

namespace App\Filament\Resources\Projects;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'projekt';
    }

    public static function getPluralModelLabel(): string
    {
        return 'projekty';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('budget_edition_id')
                ->label('Edycja')
                ->options(fn (): array => BudgetEdition::query()
                    ->orderByDesc('propose_start')
                    ->pluck('id', 'id')
                    ->all())
                ->required(),
            Select::make('project_area_id')
                ->label('Obszar')
                ->options(fn (): array => ProjectArea::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->required(),
            TextInput::make('number')
                ->label('Numer')
                ->numeric(),
            TextInput::make('number_drawn')
                ->label('Numer wylosowany')
                ->numeric(),
            TextInput::make('title')
                ->label('Tytuł')
                ->required()
                ->maxLength(600),
            Select::make('status')
                ->label('Status')
                ->options(self::statusOptions())
                ->required(),
            Textarea::make('localization')
                ->label('Lokalizacja')
                ->columnSpanFull(),
            Textarea::make('description')
                ->label('Opis')
                ->columnSpanFull(),
            Textarea::make('goal')
                ->label('Cel')
                ->columnSpanFull(),
            Textarea::make('argumentation')
                ->label('Uzasadnienie')
                ->columnSpanFull(),
            TextInput::make('cost_formatted')
                ->label('Koszt')
                ->numeric(),
            Toggle::make('is_hidden')
                ->label('Ukryty publicznie'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number_drawn')
                    ->label('Nr')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(80),
                TextColumn::make('area.name')
                    ->label('Obszar')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->adminLabel())
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    private static function statusOptions(): array
    {
        $options = [];

        foreach (ProjectStatus::cases() as $status) {
            $options[$status->value] = $status->adminLabel();
        }

        return $options;
    }
}

<?php

namespace App\Filament\Resources\BudgetEditions;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Filament\Resources\BudgetEditions\Pages\CreateBudgetEdition;
use App\Filament\Resources\BudgetEditions\Pages\EditBudgetEdition;
use App\Filament\Resources\BudgetEditions\Pages\ListBudgetEditions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetEditionResource extends Resource
{
    protected static ?string $model = BudgetEdition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getModelLabel(): string
    {
        return 'edycja SBO';
    }

    public static function getPluralModelLabel(): string
    {
        return 'edycje SBO';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('propose_start')
                ->label('Start składania')
                ->required(),
            DateTimePicker::make('propose_end')
                ->label('Koniec składania')
                ->required(),
            DateTimePicker::make('pre_voting_verification_end')
                ->label('Koniec weryfikacji przed głosowaniem')
                ->required(),
            DateTimePicker::make('voting_start')
                ->label('Start głosowania')
                ->required(),
            DateTimePicker::make('voting_end')
                ->label('Koniec głosowania')
                ->required(),
            DateTimePicker::make('post_voting_verification_end')
                ->label('Koniec weryfikacji wyników')
                ->required(),
            DateTimePicker::make('result_announcement_end')
                ->label('Koniec publikacji wyników')
                ->required(),
            Toggle::make('is_project_number_drawing')
                ->label('Losowanie numerów wykonane'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('propose_start')
                    ->label('Start')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('voting_start')
                    ->label('Głosowanie od')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('voting_end')
                    ->label('Głosowanie do')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('result_announcement_end')
                    ->label('Wyniki do')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetEditions::route('/'),
            'create' => CreateBudgetEdition::route('/create'),
            'edit' => EditBudgetEdition::route('/{record}/edit'),
        ];
    }
}

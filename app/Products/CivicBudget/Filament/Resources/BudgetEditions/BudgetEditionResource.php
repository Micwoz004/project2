<?php

namespace App\Products\CivicBudget\Filament\Resources\BudgetEditions;

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionStatus;
use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Filament\Resources\BudgetEditions\Pages\CreateBudgetEdition;
use App\Products\CivicBudget\Filament\Resources\BudgetEditions\Pages\EditBudgetEdition;
use App\Products\CivicBudget\Filament\Resources\BudgetEditions\Pages\ListBudgetEditions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

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
            Section::make('Identyfikacja edycji')
                ->schema([
                    TextInput::make('name')
                        ->label('Nazwa edycji')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('year')
                        ->label('Rok')
                        ->required()
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->default((int) date('Y')),
                    Select::make('status')
                        ->label('Status edycji')
                        ->options(BudgetEditionStatus::options())
                        ->default(BudgetEditionStatus::Active->value)
                        ->required()
                        ->native(false),
                    Toggle::make('is_project_number_drawing')
                        ->label('Losowanie numerów wykonane'),
                ])
                ->columns(4),
            Section::make('Harmonogram')
                ->schema([
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
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Rok')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (BudgetEditionStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (BudgetEditionStatus $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('propose_start')
                    ->label('Składanie od')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('propose_end')
                    ->label('Składanie do')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pre_voting_verification_end')
                    ->label('Weryfikacja do')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BudgetEditionStatus::options()),
                SelectFilter::make('year')
                    ->label('Rok')
                    ->options(fn (): array => BudgetEdition::query()
                        ->select('year')
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->all()),
            ])
            ->recordActions([
                self::activateAction(),
                self::deactivateAction(),
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

    private static function activateAction(): Action
    {
        return Action::make('activate')
            ->label('Aktywuj')
            ->requiresConfirmation()
            ->visible(fn (BudgetEdition $record): bool => $record->status === BudgetEditionStatus::Inactive)
            ->action(fn (BudgetEdition $record): BudgetEdition => self::updateStatusFromAdmin($record, BudgetEditionStatus::Active));
    }

    private static function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Dezaktywuj')
            ->requiresConfirmation()
            ->visible(fn (BudgetEdition $record): bool => $record->status !== BudgetEditionStatus::Inactive)
            ->action(fn (BudgetEdition $record): BudgetEdition => self::updateStatusFromAdmin($record, BudgetEditionStatus::Inactive));
    }

    private static function updateStatusFromAdmin(BudgetEdition $edition, BudgetEditionStatus $status): BudgetEdition
    {
        Log::info('budget_edition.status.update.start', [
            'budget_edition_id' => $edition->id,
            'target_status' => $status->value,
        ]);

        $edition->forceFill([
            'status' => $status,
        ])->save();

        Log::info('budget_edition.status.update.success', [
            'budget_edition_id' => $edition->id,
            'status' => $status->value,
        ]);

        return $edition;
    }
}

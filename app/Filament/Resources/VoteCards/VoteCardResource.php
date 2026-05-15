<?php

namespace App\Filament\Resources\VoteCards;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Voting\Actions\RegisterPaperVoteCardAction;
use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Filament\Resources\VoteCards\Pages\EditVoteCard;
use App\Filament\Resources\VoteCards\Pages\ListVoteCards;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VoteCardResource extends Resource
{
    protected static ?string $model = VoteCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return 'karta głosowania';
    }

    public static function getPluralModelLabel(): string
    {
        return 'karty głosowania';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->label('Status')
                ->options(self::statusOptions())
                ->required(),
            Textarea::make('notes')
                ->label('Uwagi administracyjne')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('card_no')
                    ->label('Nr karty')
                    ->sortable(),
                IconColumn::make('digital')
                    ->label('Elektroniczna')
                    ->boolean(),
                TextColumn::make('voter.last_name')
                    ->label('Nazwisko')
                    ->searchable(),
                TextColumn::make('voter.first_name')
                    ->label('Imię')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (VoteCardStatus $state): string => $state->label())
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Utworzona')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoteCards::route('/'),
            'edit' => EditVoteCard::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function paperVoteCardFormSchema(): array
    {
        return [
            Select::make('budget_edition_id')
                ->label('Edycja')
                ->options(fn (): array => BudgetEdition::query()
                    ->orderByDesc('voting_start')
                    ->pluck('id', 'id')
                    ->all())
                ->required(),
            TextInput::make('pesel')
                ->label('PESEL')
                ->required()
                ->length(11),
            TextInput::make('first_name')
                ->label('Imię')
                ->required()
                ->maxLength(64),
            TextInput::make('last_name')
                ->label('Nazwisko')
                ->required()
                ->maxLength(64),
            TextInput::make('mother_last_name')
                ->label('Nazwisko panieńskie matki')
                ->required()
                ->maxLength(64),
            Select::make('local_project_id')
                ->label('Projekt lokalny')
                ->options(fn (): array => self::paperVoteProjectOptions(true)),
            Select::make('citywide_project_id')
                ->label('Projekt ogólnomiejski')
                ->options(fn (): array => self::paperVoteProjectOptions(false)),
            Select::make('citizen_confirm')
                ->label('Oświadczenie wyborcy')
                ->options([
                    CitizenConfirmation::Living->value => 'Mieszkaniec Szczecina',
                    CitizenConfirmation::Commuting->value => 'Uczy się / studiuje / pracuje w Szczecinie',
                ])
                ->required(),
            Toggle::make('confirm_missing_category')
                ->label('Potwierdzono brak głosu w jednej kategorii'),
            TextInput::make('parent_name')
                ->label('Imię i nazwisko rodzica/opiekuna')
                ->maxLength(200),
            Toggle::make('parent_confirm')
                ->label('Zgoda rodzica/opiekuna'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function registerPaperVoteCardFromAdminForm(array $data): VoteCard
    {
        $operator = Auth::user();

        if (! $operator instanceof User) {
            Log::warning('paper_vote_card.register.rejected_guest');

            throw new DomainException('Użytkownik musi być zalogowany.');
        }

        return app(RegisterPaperVoteCardAction::class)->execute(
            BudgetEdition::query()->findOrFail((int) $data['budget_edition_id']),
            new VoterIdentityData(
                pesel: (string) $data['pesel'],
                firstName: (string) $data['first_name'],
                lastName: (string) $data['last_name'],
                motherLastName: (string) $data['mother_last_name'],
            ),
            self::selectedProjectIds($data, 'local_project_id'),
            self::selectedProjectIds($data, 'citywide_project_id'),
            $operator,
            [
                'citizen_confirm' => CitizenConfirmation::from((int) $data['citizen_confirm']),
                'confirm_missing_category' => (bool) ($data['confirm_missing_category'] ?? false),
                'parent_name' => $data['parent_name'] ?? null,
                'parent_confirm' => (bool) ($data['parent_confirm'] ?? false),
            ],
        );
    }

    private static function statusOptions(): array
    {
        $options = [];

        foreach (VoteCardStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    private static function paperVoteProjectOptions(bool $local): array
    {
        return Project::query()
            ->with('area')
            ->where('status', ProjectStatus::Picked->value)
            ->whereHas('area', fn ($query) => $query->where('is_local', $local))
            ->orderBy('number_drawn')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Project $project): array => [
                $project->id => trim(($project->number_drawn ?? $project->id).' - '.$project->title),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private static function selectedProjectIds(array $data, string $key): array
    {
        $projectId = (int) ($data[$key] ?? 0);

        if ($projectId === 0) {
            return [];
        }

        return [$projectId];
    }
}

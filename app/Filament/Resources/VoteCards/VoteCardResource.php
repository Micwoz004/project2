<?php

namespace App\Filament\Resources\VoteCards;

use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Filament\Resources\VoteCards\Pages\EditVoteCard;
use App\Filament\Resources\VoteCards\Pages\ListVoteCards;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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

    private static function statusOptions(): array
    {
        $options = [];

        foreach (VoteCardStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}

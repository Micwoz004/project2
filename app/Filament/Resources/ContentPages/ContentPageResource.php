<?php

namespace App\Filament\Resources\ContentPages;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Settings\Models\ContentPage;
use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Ustawienia';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'symbol';

    public static function getModelLabel(): string
    {
        return 'strona treści procesu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'strony treści procesu';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('budget_edition_id')
                ->label('Edycja')
                ->options(fn (): array => BudgetEdition::query()
                    ->orderByDesc('voting_start')
                    ->pluck('id', 'id')
                    ->all())
                ->required(),
            Select::make('symbol')
                ->label('Symbol')
                ->options(self::symbolOptions())
                ->required(),
            Textarea::make('body')
                ->label('Treść HTML')
                ->rows(14)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('budget_edition_id')
                    ->label('Edycja')
                    ->sortable(),
                TextColumn::make('symbol')
                    ->label('Symbol')
                    ->formatStateUsing(fn (string $state): string => self::symbolLabel($state))
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Treść')
                    ->limit(90)
                    ->html()
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('budget_edition_id', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
            'create' => CreateContentPage::route('/create'),
            'edit' => EditContentPage::route('/{record}/edit'),
        ];
    }

    private static function symbolOptions(): array
    {
        $options = [];

        foreach (ContentPage::LEGACY_SYMBOLS as $symbol) {
            $options[$symbol] = self::symbolLabel($symbol);
        }

        return $options;
    }

    private static function symbolLabel(string $symbol): string
    {
        return match ($symbol) {
            ContentPage::SYMBOL_VOID => 'V - brak procesu',
            ContentPage::SYMBOL_STATEMENT => 'S - oświadczenia',
            ContentPage::SYMBOL_ABSENCE => 'A - brak dostępu',
            ContentPage::SYMBOL_INFORMATION => 'I - informacje',
            ContentPage::SYMBOL_THANKYOU => 'TY - podziękowanie',
            ContentPage::SYMBOL_WELCOME => 'W - powitanie',
            ContentPage::SYMBOL_TOKEN => 'T - token',
            default => $symbol,
        };
    }
}

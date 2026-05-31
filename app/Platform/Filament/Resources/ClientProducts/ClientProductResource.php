<?php

namespace App\Platform\Filament\Resources\ClientProducts;

use App\Models\User;
use App\Platform\Filament\Resources\ClientProducts\Pages\EditClientProduct;
use App\Platform\Filament\Resources\ClientProducts\Pages\ListClientProducts;
use App\Platform\Products\Enums\ProductKey;
use App\Platform\Products\Models\ClientProduct;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ClientProductResource extends Resource
{
    protected static ?string $model = ClientProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Platforma';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return 'produkt klienta';
    }

    public static function getPluralModelLabel(): string
    {
        return 'produkty klientów';
    }

    public static function canViewAny(): bool
    {
        return self::canManageProducts();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageProducts();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('client_id')
                ->label('Klient')
                ->relationship('client', 'name')
                ->disabled(),
            Select::make('product_key')
                ->label('Produkt')
                ->options(collect(ProductKey::cases())->mapWithKeys(
                    fn (ProductKey $productKey): array => [$productKey->value => $productKey->label()],
                ))
                ->disabled(),
            Toggle::make('enabled')
                ->label('Włączony'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')->label('Klient')->searchable()->sortable(),
                TextColumn::make('product_key')->label('Produkt')->formatStateUsing(
                    fn (ProductKey $state): string => $state->label(),
                ),
                IconColumn::make('enabled')->label('Włączony')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientProducts::route('/'),
            'edit' => EditClientProduct::route('/{record}/edit'),
        ];
    }

    private static function canManageProducts(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can('platform.products.manage') || $user->hasAnyRole(['admin', 'bdo']));
    }
}

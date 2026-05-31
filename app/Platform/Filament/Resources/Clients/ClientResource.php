<?php

namespace App\Platform\Filament\Resources\Clients;

use App\Models\User;
use App\Platform\Clients\Models\Client;
use App\Platform\Filament\Resources\Clients\Pages\CreateClient;
use App\Platform\Filament\Resources\Clients\Pages\EditClient;
use App\Platform\Filament\Resources\Clients\Pages\ListClients;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
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

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Platforma';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return 'klient';
    }

    public static function getPluralModelLabel(): string
    {
        return 'klienci';
    }

    public static function canViewAny(): bool
    {
        return self::canManageClients();
    }

    public static function canCreate(): bool
    {
        return self::canManageClients();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageClients();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nazwa')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(80)
                ->unique(ignoreRecord: true),
            Toggle::make('is_active')
                ->label('Aktywny')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable()->sortable(),
                IconColumn::make('is_active')->label('Aktywny')->boolean(),
                TextColumn::make('products_count')->label('Produkty')->counts('products'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    private static function canManageClients(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can('platform.clients.manage') || $user->hasAnyRole(['admin', 'bdo']));
    }
}

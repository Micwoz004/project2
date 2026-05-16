<?php

namespace App\Filament\Resources\Users;

use App\Domain\Users\Models\Department;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'użytkownik';
    }

    public static function getPluralModelLabel(): string
    {
        return 'użytkownicy';
    }

    public static function canViewAny(): bool
    {
        return self::canManageUsers();
    }

    public static function canCreate(): bool
    {
        return self::canManageUsers();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageUsers();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Login / nazwa')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('password')
                ->label('Hasło')
                ->password()
                ->revealable()
                ->maxLength(255),
            Toggle::make('status')
                ->label('Aktywny')
                ->default(true),
            TextInput::make('first_name')
                ->label('Imię')
                ->maxLength(64),
            TextInput::make('last_name')
                ->label('Nazwisko')
                ->maxLength(64),
            TextInput::make('phone')
                ->label('Telefon')
                ->maxLength(30),
            Select::make('department_id')
                ->label('Departament')
                ->options(fn (): array => Department::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all()),
            Select::make('role_names')
                ->label('Role')
                ->multiple()
                ->options(fn (): array => Role::query()
                    ->orderBy('name')
                    ->pluck('name', 'name')
                    ->all())
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Login')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Departament')
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                IconColumn::make('status')
                    ->label('Aktywny')
                    ->boolean(),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    private static function canManageUsers(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can('users.manage') || $user->hasAnyRole(['admin', 'bdo']));
    }
}

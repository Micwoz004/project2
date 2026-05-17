<?php

namespace App\Filament\Resources\ProjectPublicComments;

use App\Domain\Communications\Actions\AcceptProjectPublicCommentAction;
use App\Domain\Communications\Actions\ToggleProjectPublicCommentAdminHiddenAction;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Users\Enums\SystemRole;
use App\Filament\Resources\ProjectPublicComments\Pages\ListProjectPublicComments;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectPublicCommentResource extends Resource
{
    protected static ?string $model = ProjectPublicComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'content';

    public static function getModelLabel(): string
    {
        return 'komentarz publiczny';
    }

    public static function getPluralModelLabel(): string
    {
        return 'komentarze publiczne';
    }

    public static function canViewAny(): bool
    {
        return self::canModerate();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('content')
                ->label('Treść')
                ->disabled()
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
                TextColumn::make('project.title')
                    ->label('Projekt')
                    ->searchable()
                    ->limit(48),
                TextColumn::make('creator.name')
                    ->label('Autor')
                    ->searchable()
                    ->placeholder('Brak użytkownika'),
                TextColumn::make('content')
                    ->label('Treść')
                    ->searchable()
                    ->limit(80),
                IconColumn::make('moderated')
                    ->label('Zaakceptowany')
                    ->boolean(),
                IconColumn::make('hidden')
                    ->label('Ukryty przez autora')
                    ->boolean(),
                IconColumn::make('admin_hidden')
                    ->label('Ukryty admin.')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Utworzony')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                self::acceptAction(),
                self::toggleAdminHiddenAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectPublicComments::route('/'),
        ];
    }

    public static function acceptFromAdmin(ProjectPublicComment $comment): ProjectPublicComment
    {
        return app(AcceptProjectPublicCommentAction::class)->execute(
            $comment,
            self::authenticatedUser('public_comment.accept.rejected_guest'),
        );
    }

    public static function toggleAdminHiddenFromAdmin(ProjectPublicComment $comment): ProjectPublicComment
    {
        return app(ToggleProjectPublicCommentAdminHiddenAction::class)->execute(
            $comment,
            self::authenticatedUser('public_comment.admin_hide.rejected_guest'),
        );
    }

    private static function acceptAction(): Action
    {
        return Action::make('accept')
            ->label('Akceptuj')
            ->requiresConfirmation()
            ->visible(fn (ProjectPublicComment $record): bool => self::canModerate() && ! $record->moderated)
            ->action(fn (ProjectPublicComment $record): ProjectPublicComment => self::acceptFromAdmin($record));
    }

    private static function toggleAdminHiddenAction(): Action
    {
        return Action::make('toggle_admin_hidden')
            ->label(fn (ProjectPublicComment $record): string => $record->admin_hidden ? 'Przywróć' : 'Ukryj admin.')
            ->requiresConfirmation()
            ->visible(fn (): bool => self::canModerate())
            ->action(fn (ProjectPublicComment $record): ProjectPublicComment => self::toggleAdminHiddenFromAdmin($record));
    }

    private static function canModerate(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(SystemRole::Admin->value);
    }

    private static function authenticatedUser(string $rejectionContext): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            Log::warning($rejectionContext);

            throw new DomainException('Użytkownik musi być zalogowany.');
        }

        return $user;
    }
}

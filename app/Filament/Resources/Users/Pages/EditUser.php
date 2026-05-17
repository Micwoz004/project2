<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Users\Actions\AnonymizeUserAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use DomainException;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('anonymizeUser')
                ->label('Anonimizuj konto')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $operator = Auth::user();

                    if (! $operator instanceof User) {
                        Log::warning('user.anonymize.rejected_guest');

                        throw new DomainException('Użytkownik musi być zalogowany.');
                    }

                    app(AnonymizeUserAction::class)->execute($this->getRecord(), $operator);

                    $this->redirect(UserResource::getUrl('index'));
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();
        $data['role_names'] = $user->roles()->pluck('name')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $roles = $data['role_names'] ?? [];
        unset($data['role_names']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        /** @var User $record */
        $record->update($data);
        $record->syncRoles($roles);

        return $record;
    }
}

<?php

namespace App\Domain\Users\Actions;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnonymizeUserAction
{
    public function execute(User $user, User $operator): User
    {
        Log::info('user.anonymize.start', [
            'user_id' => $user->id,
            'operator_id' => $operator->id,
        ]);

        if (! $this->canAnonymize($operator)) {
            Log::warning('user.anonymize.rejected_unauthorized', [
                'user_id' => $user->id,
                'operator_id' => $operator->id,
            ]);

            throw new DomainException('Brak uprawnienia do anonimizacji użytkownika.');
        }

        $uniqueId = (string) Str::ulid();

        DB::transaction(function () use ($user, $uniqueId): void {
            $user->forceFill([
                'name' => 'deleted-'.$uniqueId,
                'email' => 'deleted-'.$uniqueId.'@anonymous.local',
                'email_verified_at' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => Str::random(60),
                'status' => false,
                'pesel' => '*',
                'first_name' => 'Konto',
                'last_name' => 'Usunięte',
                'phone' => '*',
                'street' => '*',
                'house_no' => '*',
                'flat_no' => '*',
                'post_code' => '*',
                'city' => '*',
                'department_id' => null,
                'department_text' => null,
            ])->save();

            $user->syncRoles([]);
        });

        Log::info('user.anonymize.success', [
            'user_id' => $user->id,
            'operator_id' => $operator->id,
        ]);

        return $user->refresh();
    }

    private function canAnonymize(User $operator): bool
    {
        return $operator->can('users.manage') || $operator->hasAnyRole(['admin', 'bdo']);
    }
}

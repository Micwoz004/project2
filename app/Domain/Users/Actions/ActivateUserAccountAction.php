<?php

namespace App\Domain\Users\Actions;

use App\Domain\Users\Enums\ActivationTokenType;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivateUserAccountAction
{
    public function __construct(
        private readonly ResolveUserActivationTokenAction $resolveUserActivationToken,
    ) {}

    public function execute(int $id, string $hash): User
    {
        Log::info('user.account.activate.start', [
            'activation_id' => $id,
        ]);

        $token = $this->resolveUserActivationToken->execute($id, $hash, ActivationTokenType::AccountActivationEmail);

        $user = DB::transaction(function () use ($token): User {
            $user = $token->user;
            $user->forceFill([
                'status' => true,
            ])->save();
            $user->assignRole(SystemRole::Applicant->value);
            $token->delete();

            return $user->refresh();
        });

        Log::info('user.account.activate.success', [
            'activation_id' => $id,
            'user_id' => $user->id,
        ]);

        return $user;
    }
}

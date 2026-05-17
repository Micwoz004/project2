<?php

namespace App\Domain\Users\Actions;

use App\Domain\Users\Enums\ActivationTokenType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetUserPasswordAction
{
    public function __construct(
        private readonly ResolveUserActivationTokenAction $resolveUserActivationToken,
    ) {}

    public function execute(int $id, string $hash, string $password): User
    {
        Log::info('user.password_reset.apply.start', [
            'activation_id' => $id,
        ]);

        $token = $this->resolveUserActivationToken->execute($id, $hash, ActivationTokenType::PasswordReset);

        $user = DB::transaction(function () use ($token, $password): User {
            $user = $token->user;
            $user->forceFill([
                'password' => $password,
            ])->save();
            $token->delete();

            return $user->refresh();
        });

        Log::info('user.password_reset.apply.success', [
            'activation_id' => $id,
            'user_id' => $user->id,
        ]);

        return $user;
    }
}

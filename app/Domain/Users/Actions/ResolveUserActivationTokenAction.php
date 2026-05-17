<?php

namespace App\Domain\Users\Actions;

use App\Domain\Settings\Services\ApplicationSettings;
use App\Domain\Users\Enums\ActivationTokenType;
use App\Domain\Users\Models\UserActivationToken;
use DomainException;
use Illuminate\Support\Facades\Log;

class ResolveUserActivationTokenAction
{
    public function __construct(
        private readonly ApplicationSettings $settings,
    ) {}

    public function execute(int $id, string $hash, ActivationTokenType $type): UserActivationToken
    {
        Log::info('user.activation_token.resolve.start', [
            'activation_id' => $id,
            'type' => $type->value,
        ]);

        $token = UserActivationToken::query()
            ->where('id', $id)
            ->where('hash', $hash)
            ->where('type', $type->value)
            ->first();

        if (! $token instanceof UserActivationToken || $this->isExpired($token)) {
            Log::warning('user.activation_token.resolve.rejected', [
                'activation_id' => $id,
                'type' => $type->value,
            ]);

            throw new DomainException('Link aktywacyjny jest nieprawidłowy albo wygasł.');
        }

        Log::info('user.activation_token.resolve.success', [
            'activation_id' => $token->id,
            'user_id' => $token->user_id,
            'type' => $type->value,
        ]);

        return $token;
    }

    private function isExpired(UserActivationToken $token): bool
    {
        $lifetimeHours = $this->settings->integer('system', 'activationLinkLifetime', 24);

        return $token->created_at->copy()->addHours($lifetimeHours)->lte(now());
    }
}

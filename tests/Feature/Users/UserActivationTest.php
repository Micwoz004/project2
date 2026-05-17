<?php

use App\Domain\Settings\Models\ApplicationSetting;
use App\Domain\Users\Actions\ActivateUserAccountAction;
use App\Domain\Users\Actions\ResetUserPasswordAction;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\ActivationTokenType;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Users\Models\UserActivationToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('activates account email token within legacy lifetime and assigns applicant role', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    activationLifetime(24);

    $user = User::factory()->create([
        'status' => false,
    ]);
    $token = activationToken($user, ActivationTokenType::AccountActivationEmail, now()->subHours(2));

    $activated = app(ActivateUserAccountAction::class)->execute($token->id, $token->hash);

    expect($activated->status)->toBeTrue()
        ->and($activated->hasRole(SystemRole::Applicant->value))->toBeTrue()
        ->and(UserActivationToken::query()->whereKey($token->id)->exists())->toBeFalse();
});

it('rejects expired account activation token using system activation lifetime', function (): void {
    activationLifetime(1);

    $user = User::factory()->create([
        'status' => false,
    ]);
    $token = activationToken($user, ActivationTokenType::AccountActivationEmail, now()->subHours(2));

    app(ActivateUserAccountAction::class)->execute($token->id, $token->hash);
})->throws(DomainException::class, 'Link aktywacyjny jest nieprawidłowy albo wygasł.');

it('resets password with valid legacy password reset token and deletes token', function (): void {
    activationLifetime(24);

    $user = User::factory()->create([
        'password' => 'old-password',
    ]);
    $token = activationToken($user, ActivationTokenType::PasswordReset, now()->subMinutes(15));

    $updated = app(ResetUserPasswordAction::class)->execute($token->id, $token->hash, 'new-secret-password');

    expect(Hash::check('new-secret-password', $updated->password))->toBeTrue()
        ->and(UserActivationToken::query()->whereKey($token->id)->exists())->toBeFalse();
});

function activationLifetime(int $hours): void
{
    ApplicationSetting::query()->updateOrCreate([
        'category' => 'system',
        'key' => 'activationLinkLifetime',
    ], [
        'value' => (string) $hours,
    ]);
}

function activationToken(User $user, ActivationTokenType $type, DateTimeInterface $createdAt): UserActivationToken
{
    return UserActivationToken::query()->create([
        'user_id' => $user->id,
        'hash' => 'legacy-activation-hash-'.$type->value.'-'.$user->id,
        'type' => $type,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

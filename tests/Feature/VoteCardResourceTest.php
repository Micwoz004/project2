<?php

use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Filament\Resources\VoteCards\VoteCardResource;
use App\Models\User;

it('registers vote card filament resource pages and blocks creation', function (): void {
    expect(VoteCardResource::canCreate())->toBeFalse()
        ->and(array_keys(VoteCardResource::getPages()))->toBe(['index', 'edit']);
});

it('allows vote card resource access through vote card policy', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    $operator = User::factory()->create();
    $operator->assignRole(SystemRole::CheckVoter->value);
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);

    $this->actingAs($operator);
    expect(VoteCardResource::canViewAny())->toBeTrue();

    $this->actingAs($applicant);
    expect(VoteCardResource::canViewAny())->toBeFalse();
});

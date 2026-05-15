<?php

use App\Domain\Users\Services\LegacyRbacImportService;
use App\Models\User;

it('imports legacy rbac items children and user assignments', function (): void {
    $user = User::factory()->create([
        'legacy_id' => 100,
    ]);

    $stats = app(LegacyRbacImportService::class)->import([
        'authitem' => [
            ['name' => 'custom coordinator', 'type' => 2],
            ['name' => 'custom operation', 'type' => 0],
        ],
        'authitemchild' => [
            ['parent' => 'custom coordinator', 'child' => 'custom operation'],
        ],
        'authassignment' => [
            ['itemname' => 'custom coordinator', 'userid' => 100],
        ],
    ]);

    expect($stats)->toBe([
        'authitem' => 2,
        'authitemchild' => 1,
        'authassignment' => 1,
    ])
        ->and($user->refresh()->hasRole('custom coordinator'))->toBeTrue()
        ->and($user->can('custom operation'))->toBeTrue();
});

it('rejects legacy rbac assignments for missing users', function (): void {
    app(LegacyRbacImportService::class)->import([
        'authitem' => [
            ['name' => 'custom coordinator', 'type' => 2],
        ],
        'authassignment' => [
            ['itemname' => 'custom coordinator', 'userid' => 999],
        ],
    ]);
})->throws(DomainException::class, 'Brak użytkownika legacy dla przypisania RBAC.');

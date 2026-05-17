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

it('maps legacy rbac operations to canonical laravel permissions', function (): void {
    $roleUser = User::factory()->create([
        'legacy_id' => 110,
    ]);
    $directPermissionUser = User::factory()->create([
        'legacy_id' => 111,
    ]);

    app(LegacyRbacImportService::class)->import([
        'authitem' => [
            ['name' => 'custom manager', 'type' => 2],
            ['name' => 'manage users', 'type' => 0],
            ['name' => 'generate reports', 'type' => 0],
        ],
        'authitemchild' => [
            ['parent' => 'custom manager', 'child' => 'manage users'],
        ],
        'authassignment' => [
            ['itemname' => 'custom manager', 'userid' => 110],
            ['itemname' => 'generate reports', 'userid' => 111],
        ],
    ]);

    expect($roleUser->refresh()->can('users.manage'))->toBeTrue()
        ->and($directPermissionUser->refresh()->can('reports.export'))->toBeTrue()
        ->and($directPermissionUser->can('results.view'))->toBeTrue();
});

it('flattens nested legacy rbac role and permission children', function (): void {
    $roleUser = User::factory()->create([
        'legacy_id' => 101,
    ]);
    $permissionUser = User::factory()->create([
        'legacy_id' => 102,
    ]);

    app(LegacyRbacImportService::class)->import([
        'authitem' => [
            ['name' => 'lead role', 'type' => 2],
            ['name' => 'nested role', 'type' => 2],
            ['name' => 'parent operation', 'type' => 0],
            ['name' => 'leaf operation', 'type' => 0],
        ],
        'authitemchild' => [
            ['parent' => 'lead role', 'child' => 'nested role'],
            ['parent' => 'nested role', 'child' => 'parent operation'],
            ['parent' => 'parent operation', 'child' => 'leaf operation'],
        ],
        'authassignment' => [
            ['itemname' => 'lead role', 'userid' => 101],
            ['itemname' => 'parent operation', 'userid' => 102],
        ],
    ]);

    expect($roleUser->refresh()->can('parent operation'))->toBeTrue()
        ->and($roleUser->can('leaf operation'))->toBeTrue()
        ->and($permissionUser->refresh()->can('parent operation'))->toBeTrue()
        ->and($permissionUser->can('leaf operation'))->toBeTrue();
});

it('skips legacy rbac assignments for missing users', function (): void {
    $stats = app(LegacyRbacImportService::class)->import([
        'authitem' => [
            ['name' => 'custom coordinator', 'type' => 2],
        ],
        'authassignment' => [
            ['itemname' => 'custom coordinator', 'userid' => 999],
        ],
    ]);

    expect($stats['authassignment'])->toBe(0);
});

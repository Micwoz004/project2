<?php

use App\Domain\Users\Models\Department;
use App\Domain\Users\Services\LegacyUserImportService;
use App\Models\User;

it('imports legacy departments and users by legacy ids', function (): void {
    $stats = app(LegacyUserImportService::class)->import([
        'departments' => [
            ['id' => 10, 'name' => 'Biuro Dialogu Obywatelskiego'],
        ],
        'users' => [
            [
                'id' => 20,
                'username' => 'legacy.admin',
                'email' => 'legacy.admin@example.test',
                'status' => true,
                'firstName' => 'Jan',
                'lastName' => 'Kowalski',
                'departmentId' => 10,
            ],
        ],
    ]);

    $department = Department::query()->where('legacy_id', 10)->firstOrFail();
    $user = User::query()->where('legacy_id', 20)->firstOrFail();

    expect($stats)->toBe(['departments' => 1, 'users' => 1])
        ->and($department->name)->toBe('Biuro Dialogu Obywatelskiego')
        ->and($user->department_id)->toBe($department->id)
        ->and($user->status)->toBeTrue()
        ->and($user->first_name)->toBe('Jan');
});

it('keeps legacy user import idempotent and creates placeholder email when missing', function (): void {
    $payload = [
        'users' => [
            [
                'id' => 30,
                'username' => 'legacy.noemail',
                'email' => null,
                'status' => false,
            ],
        ],
    ];

    app(LegacyUserImportService::class)->import($payload);
    app(LegacyUserImportService::class)->import($payload);

    $user = User::query()->where('legacy_id', 30)->firstOrFail();

    expect(User::query()->where('legacy_id', 30)->count())->toBe(1)
        ->and($user->email)->toBe('legacy-user-30@invalid.local')
        ->and($user->status)->toBeFalse();
});

it('creates unique technical emails for anonymized and duplicate legacy users', function (): void {
    app(LegacyUserImportService::class)->import([
        'users' => [
            [
                'id' => 40,
                'username' => 'deleted-old-a',
                'email' => '*',
                'status' => false,
            ],
            [
                'id' => 41,
                'username' => 'deleted-old-b',
                'email' => '*',
                'status' => false,
            ],
            [
                'id' => 42,
                'username' => 'legacy.duplicate.a',
                'email' => 'duplicate@example.test',
                'status' => true,
            ],
            [
                'id' => 43,
                'username' => 'legacy.duplicate.b',
                'email' => 'duplicate@example.test',
                'status' => true,
            ],
        ],
    ]);

    expect(User::query()->where('legacy_id', 40)->firstOrFail()->email)->toBe('deleted-40@anonymous.local')
        ->and(User::query()->where('legacy_id', 41)->firstOrFail()->email)->toBe('deleted-41@anonymous.local')
        ->and(User::query()->where('legacy_id', 42)->firstOrFail()->email)->toBe('duplicate@example.test')
        ->and(User::query()->where('legacy_id', 43)->firstOrFail()->email)->toBe('legacy-user-43@duplicate.local');
});

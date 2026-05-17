<?php

use App\Domain\Users\Actions\AnonymizeUserAction;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Users\Models\Department;
use App\Models\User;

it('anonymizes user account instead of deleting it like legacy', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $department = Department::query()->create(['name' => 'Wydział Testowy']);
    $operator = User::factory()->create(['status' => true]);
    $operator->givePermissionTo(SystemPermission::UsersManage->value);
    $user = User::factory()->create([
        'status' => true,
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'phone' => '500600700',
        'street' => 'Jasna',
        'house_no' => '1',
        'flat_no' => '2',
        'post_code' => '70-001',
        'city' => 'Szczecin',
        'department_id' => $department->id,
        'department_text' => 'Opis departamentu',
    ]);
    $user->assignRole(SystemRole::Applicant->value);

    $anonymized = app(AnonymizeUserAction::class)->execute($user, $operator);

    expect(str_starts_with($anonymized->name, 'deleted-'))->toBeTrue()
        ->and(str_starts_with($anonymized->email, 'deleted-'))->toBeTrue()
        ->and(str_ends_with($anonymized->email, '@anonymous.local'))->toBeTrue()
        ->and($anonymized->email_verified_at)->toBeNull()
        ->and($anonymized->status)->toBeFalse()
        ->and($anonymized->pesel)->toBe('*')
        ->and($anonymized->first_name)->toBe('Konto')
        ->and($anonymized->last_name)->toBe('Usunięte')
        ->and($anonymized->phone)->toBe('*')
        ->and($anonymized->street)->toBe('*')
        ->and($anonymized->house_no)->toBe('*')
        ->and($anonymized->flat_no)->toBe('*')
        ->and($anonymized->post_code)->toBe('*')
        ->and($anonymized->city)->toBe('*')
        ->and($anonymized->department_id)->toBeNull()
        ->and($anonymized->department_text)->toBeNull()
        ->and($anonymized->roles()->count())->toBe(0);
});

it('rejects user anonymization without user management permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $operator = User::factory()->create(['status' => true]);
    $operator->assignRole(SystemRole::Applicant->value);
    $user = User::factory()->create(['status' => true]);

    app(AnonymizeUserAction::class)->execute($user, $operator);
})->throws(DomainException::class, 'Brak uprawnienia do anonimizacji użytkownika.');

<?php

use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Users\Models\Department;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers user filament resource pages and gates access by users permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);

    $this->actingAs($admin);
    expect(UserResource::canViewAny())->toBeTrue()
        ->and(UserResource::canCreate())->toBeTrue()
        ->and(array_keys(UserResource::getPages()))->toBe(['index', 'create', 'edit']);

    $this->actingAs($applicant);
    expect(UserResource::canViewAny())->toBeFalse()
        ->and(UserResource::canCreate())->toBeFalse();
});

it('creates user from filament form and syncs roles', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $department = Department::query()->create(['name' => 'Biuro Dialogu Obywatelskiego']);

    $page = new CreateUser;
    $method = new ReflectionMethod($page, 'handleRecordCreation');
    $method->setAccessible(true);

    /** @var User $user */
    $user = $method->invoke($page, [
        'name' => 'legacy.operator',
        'email' => 'operator@example.test',
        'password' => 'tajne-haslo',
        'status' => true,
        'first_name' => 'Jan',
        'last_name' => 'Operator',
        'department_id' => $department->id,
        'role_names' => [SystemRole::CheckVoter->value],
    ]);

    expect($user->email)->toBe('operator@example.test')
        ->and($user->department_id)->toBe($department->id)
        ->and($user->hasRole(SystemRole::CheckVoter->value))->toBeTrue()
        ->and(Hash::check('tajne-haslo', $user->password))->toBeTrue();
});

it('updates user from filament form without clearing password when blank', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $department = Department::query()->create(['name' => 'Wydział Inwestycji']);
    $user = User::factory()->create([
        'password' => 'stare-haslo',
        'status' => true,
    ]);
    $user->assignRole(SystemRole::Applicant->value);
    $oldPassword = $user->password;

    $page = new EditUser;
    $method = new ReflectionMethod($page, 'handleRecordUpdate');
    $method->setAccessible(true);

    $method->invoke($page, $user, [
        'name' => 'updated.operator',
        'email' => $user->email,
        'password' => '',
        'status' => false,
        'first_name' => 'Anna',
        'last_name' => 'Operatorka',
        'department_id' => $department->id,
        'role_names' => [SystemRole::Coordinator->value],
    ]);

    $user->refresh();

    expect($user->name)->toBe('updated.operator')
        ->and($user->status)->toBeFalse()
        ->and($user->department_id)->toBe($department->id)
        ->and($user->password)->toBe($oldPassword)
        ->and($user->hasRole(SystemRole::Coordinator->value))->toBeTrue()
        ->and($user->hasRole(SystemRole::Applicant->value))->toBeFalse();
});

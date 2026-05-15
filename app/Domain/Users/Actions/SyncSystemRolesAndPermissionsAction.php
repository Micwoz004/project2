<?php

namespace App\Domain\Users\Actions;

use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncSystemRolesAndPermissionsAction
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function execute(string $guardName = 'web'): void
    {
        Log::info('Synchronizing RBAC roles and permissions', [
            'guard' => $guardName,
            'roles' => count(SystemRole::defaultPermissions()),
            'permissions' => count(SystemPermission::cases()),
        ]);

        foreach (SystemPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, $guardName);
        }

        foreach (array_keys(SystemPermission::legacyPermissionMap()) as $legacyPermission) {
            Permission::findOrCreate($legacyPermission, $guardName);
        }

        foreach (SystemRole::defaultPermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, $guardName);
            $role->syncPermissions(array_map(
                static fn (SystemPermission $permission): string => $permission->value,
                $permissions,
            ));
        }

        $this->permissionRegistrar->forgetCachedPermissions();

        Log::info('RBAC synchronization finished', [
            'guard' => $guardName,
        ]);
    }
}

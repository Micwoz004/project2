<?php

namespace App\Platform\Users\Actions;

use App\Platform\Clients\Services\CurrentClient;
use App\Platform\Products\Enums\ProductKey;
use App\Platform\Products\Models\ClientProduct;
use App\Platform\Users\Enums\SystemPermission;
use App\Platform\Users\Enums\SystemRole;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncSystemRolesAndPermissionsAction
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
        private readonly CurrentClient $currentClient,
    ) {}

    public function execute(string $guardName = 'web'): void
    {
        $client = $this->currentClient->require();
        $this->ensureDefaultProducts($client->id);

        Log::info('Synchronizing RBAC roles and permissions', [
            'guard' => $guardName,
            'client_id' => $client->id,
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
            'client_id' => $client->id,
        ]);
    }

    private function ensureDefaultProducts(int $clientId): void
    {
        foreach (ProductKey::cases() as $productKey) {
            ClientProduct::query()->firstOrCreate([
                'client_id' => $clientId,
                'product_key' => $productKey->value,
            ], [
                'enabled' => in_array($productKey->value, config('platform.default_products', []), true),
                'settings' => [],
            ]);
        }
    }
}

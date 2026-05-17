<?php

namespace App\Domain\Users\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LegacyRbacImportService
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  array<string, list<array<string, mixed>>>  $payload
     * @return array<string, int>
     */
    public function import(array $payload, string $guardName = 'web'): array
    {
        Log::info('legacy_rbac_import.start', [
            'guard' => $guardName,
        ]);

        $childrenByParent = $this->childrenByParent($payload['authitemchild'] ?? []);

        $stats = [
            'authitem' => $this->importAuthItems($payload['authitem'] ?? [], $guardName),
            'authitemchild' => $this->importAuthItemChildren($payload['authitemchild'] ?? [], $guardName, $childrenByParent),
            'authassignment' => $this->importAuthAssignments($payload['authassignment'] ?? [], $guardName, $childrenByParent),
        ];

        $this->permissionRegistrar->forgetCachedPermissions();

        Log::info('legacy_rbac_import.success', [
            'guard' => $guardName,
            'items_count' => array_sum($stats),
        ]);

        return $stats;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importAuthItems(array $rows, string $guardName): int
    {
        foreach ($rows as $row) {
            $name = (string) Arr::get($row, 'name');
            $type = (int) Arr::get($row, 'type');

            if ($type === 2) {
                Role::findOrCreate($name, $guardName);

                continue;
            }

            Permission::findOrCreate($name, $guardName);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, list<string>>  $childrenByParent
     */
    private function importAuthItemChildren(array $rows, string $guardName, array $childrenByParent): int
    {
        foreach (array_keys($childrenByParent) as $parentName) {
            if (! $this->roleExists($parentName, $guardName)) {
                $this->assertKnownItem($parentName, $guardName);

                continue;
            }

            $permissions = $this->permissionsForItem($parentName, $childrenByParent, $guardName);

            if ($permissions !== []) {
                Role::findByName($parentName, $guardName)->givePermissionTo($permissions);
            }
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, list<string>>  $childrenByParent
     */
    private function importAuthAssignments(array $rows, string $guardName, array $childrenByParent): int
    {
        $imported = 0;

        foreach ($rows as $row) {
            $user = User::query()->where('legacy_id', (int) Arr::get($row, 'userid'))->first();

            if (! $user instanceof User) {
                Log::warning('legacy_rbac_import.assignment_missing_user', [
                    'legacy_user_id' => Arr::get($row, 'userid'),
                ]);

                continue;
            }

            $itemName = (string) Arr::get($row, 'itemname');

            if ($this->roleExists($itemName, $guardName)) {
                $user->assignRole($itemName);
                $imported++;

                continue;
            }

            $this->assertKnownItem($itemName, $guardName);

            $user->givePermissionTo(array_values(array_unique([
                $itemName,
                ...$this->permissionsForItem($itemName, $childrenByParent, $guardName),
            ])));

            $imported++;
        }

        return $imported;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<string>>
     */
    private function childrenByParent(array $rows): array
    {
        $childrenByParent = [];

        foreach ($rows as $row) {
            $parentName = (string) Arr::get($row, 'parent');
            $childName = (string) Arr::get($row, 'child');

            $childrenByParent[$parentName][] = $childName;
        }

        return $childrenByParent;
    }

    /**
     * @param  array<string, list<string>>  $childrenByParent
     * @param  array<string, true>  $visited
     * @return list<string>
     */
    private function permissionsForItem(string $itemName, array $childrenByParent, string $guardName, array $visited = []): array
    {
        if (isset($visited[$itemName])) {
            Log::warning('legacy_rbac_import.cycle_skipped', [
                'item' => $itemName,
            ]);

            return [];
        }

        $visited[$itemName] = true;
        $permissions = [];

        foreach ($childrenByParent[$itemName] ?? [] as $childName) {
            $this->assertKnownItem($childName, $guardName);

            if ($this->permissionExists($childName, $guardName)) {
                $permissions[] = $childName;
            }

            $permissions = [
                ...$permissions,
                ...$this->permissionsForItem($childName, $childrenByParent, $guardName, $visited),
            ];
        }

        return array_values(array_unique($permissions));
    }

    private function assertKnownItem(string $itemName, string $guardName): void
    {
        if ($this->roleExists($itemName, $guardName) || $this->permissionExists($itemName, $guardName)) {
            return;
        }

        Log::warning('legacy_rbac_import.unknown_item', [
            'item' => $itemName,
            'guard' => $guardName,
        ]);

        throw new DomainException('Nieznany element RBAC legacy.');
    }

    private function roleExists(string $roleName, string $guardName): bool
    {
        return Role::query()
            ->where('name', $roleName)
            ->where('guard_name', $guardName)
            ->exists();
    }

    private function permissionExists(string $permissionName, string $guardName): bool
    {
        return Permission::query()
            ->where('name', $permissionName)
            ->where('guard_name', $guardName)
            ->exists();
    }
}

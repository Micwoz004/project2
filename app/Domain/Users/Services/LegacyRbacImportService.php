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

        $stats = [
            'authitem' => $this->importAuthItems($payload['authitem'] ?? [], $guardName),
            'authitemchild' => $this->importAuthItemChildren($payload['authitemchild'] ?? [], $guardName),
            'authassignment' => $this->importAuthAssignments($payload['authassignment'] ?? [], $guardName),
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
     */
    private function importAuthItemChildren(array $rows, string $guardName): int
    {
        foreach ($rows as $row) {
            $parentName = (string) Arr::get($row, 'parent');
            $childName = (string) Arr::get($row, 'child');
            $parentRole = Role::findByName($parentName, $guardName);

            if (Permission::query()->where('name', $childName)->where('guard_name', $guardName)->exists()) {
                $parentRole->givePermissionTo($childName);

                continue;
            }

            $childRole = Role::findByName($childName, $guardName);
            $parentRole->givePermissionTo($childRole->permissions);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importAuthAssignments(array $rows, string $guardName): int
    {
        foreach ($rows as $row) {
            $user = User::query()->where('legacy_id', (int) Arr::get($row, 'userid'))->first();

            if (! $user instanceof User) {
                Log::warning('legacy_rbac_import.assignment_missing_user', [
                    'legacy_user_id' => Arr::get($row, 'userid'),
                ]);

                throw new DomainException('Brak użytkownika legacy dla przypisania RBAC.');
            }

            $itemName = (string) Arr::get($row, 'itemname');

            if (Role::query()->where('name', $itemName)->where('guard_name', $guardName)->exists()) {
                $user->assignRole($itemName);

                continue;
            }

            $user->givePermissionTo($itemName);
        }

        return count($rows);
    }
}

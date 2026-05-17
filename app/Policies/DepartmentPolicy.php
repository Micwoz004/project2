<?php

namespace App\Policies;

use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesUsers($user);
    }

    public function view(User $user, Department $department): bool
    {
        return $this->managesUsers($user);
    }

    public function create(User $user): bool
    {
        return $this->managesUsers($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->managesUsers($user);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->managesUsers($user);
    }

    private function managesUsers(User $user): bool
    {
        return $user->can(SystemPermission::UsersManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}

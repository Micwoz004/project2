<?php

namespace App\Policies;

use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Verification\Models\ProjectAppeal;
use App\Models\User;

class ProjectAppealPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesProjects($user);
    }

    public function view(User $user, ProjectAppeal $projectAppeal): bool
    {
        return $this->managesProjects($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ProjectAppeal $projectAppeal): bool
    {
        return false;
    }

    public function delete(User $user, ProjectAppeal $projectAppeal): bool
    {
        return false;
    }

    private function managesProjects(User $user): bool
    {
        return $user->can(SystemPermission::ProjectsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}

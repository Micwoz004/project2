<?php

namespace App\Policies;

use App\Domain\Projects\Models\ProjectChangeSuggestion;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

class ProjectChangeSuggestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesCorrections($user);
    }

    public function view(User $user, ProjectChangeSuggestion $projectChangeSuggestion): bool
    {
        return $this->managesCorrections($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ProjectChangeSuggestion $projectChangeSuggestion): bool
    {
        return false;
    }

    public function delete(User $user, ProjectChangeSuggestion $projectChangeSuggestion): bool
    {
        return false;
    }

    private function managesCorrections(User $user): bool
    {
        return $user->can(SystemPermission::ProjectCorrectionsManage->value)
            || $user->can(SystemPermission::ProjectsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}

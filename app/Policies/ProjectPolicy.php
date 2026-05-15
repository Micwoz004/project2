<?php

namespace App\Policies;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Services\ProjectLifecycleService;
use App\Models\User;

class ProjectPolicy
{
    public function __construct(
        private readonly ProjectLifecycleService $lifecycle,
    ) {}

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Project $project): bool
    {
        return $this->lifecycle->isPubliclyVisible($project) || $this->managesProjects($user);
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->managesProjects($user)
            || ($project->creator_id === $user->id && $this->lifecycle->canApplicantEdit($project));
    }

    public function submit(?User $user, Project $project): bool
    {
        return $this->lifecycle->canSubmit($project)
            && ($this->managesProjects($user) || $project->creator_id === $user?->id || $user === null);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->managesProjects($user) && $this->lifecycle->canApplicantEdit($project);
    }

    private function managesProjects(?User $user): bool
    {
        return $user?->can('projects.manage') === true
            || $user?->hasAnyRole(['admin', 'bdo']) === true;
    }
}

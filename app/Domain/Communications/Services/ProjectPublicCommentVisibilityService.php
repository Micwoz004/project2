<?php

namespace App\Domain\Communications\Services;

use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;

class ProjectPublicCommentVisibilityService
{
    public function canView(ProjectPublicComment $comment, ?User $viewer): bool
    {
        if ($comment->moderated && ! $comment->hidden && ! $comment->admin_hidden) {
            return true;
        }

        if (! $viewer instanceof User) {
            return false;
        }

        if (! $comment->moderated && ($this->isCreator($comment, $viewer) || $this->isAdmin($viewer) || $this->isProjectCreator($comment, $viewer))) {
            return true;
        }

        if ($comment->hidden && ($this->isCreator($comment, $viewer) || $this->isAdmin($viewer))) {
            return true;
        }

        return $comment->admin_hidden && $this->isAdmin($viewer);
    }

    private function isCreator(ProjectPublicComment $comment, User $viewer): bool
    {
        return $comment->created_by_id === $viewer->id;
    }

    private function isProjectCreator(ProjectPublicComment $comment, User $viewer): bool
    {
        return $comment->project instanceof Project && $comment->project->creator_id === $viewer->id;
    }

    private function isAdmin(User $viewer): bool
    {
        return $viewer->hasRole(SystemRole::Admin->value);
    }
}

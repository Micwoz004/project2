<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Models\ProjectComment;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class AddProjectCommentAction
{
    public function execute(Project $project, User $user, string $content): ProjectComment
    {
        Log::info('project_comment.add.start', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        if (! $this->canComment($user)) {
            Log::warning('project_comment.add.rejected_permission', [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Brak uprawnień do dodania komentarza.');
        }

        if (trim($content) === '') {
            Log::warning('project_comment.add.rejected_empty_content', [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Treść komentarza nie może być pusta.');
        }

        $comment = ProjectComment::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'content' => $content,
        ]);

        Log::info('project_comment.add.success', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);

        return $comment;
    }

    private function canComment(User $user): bool
    {
        return $user->can('projects.manage')
            || $user->can('projects.verify')
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}

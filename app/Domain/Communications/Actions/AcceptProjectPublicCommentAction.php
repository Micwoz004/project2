<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class AcceptProjectPublicCommentAction
{
    public function execute(ProjectPublicComment $comment, User $user): ProjectPublicComment
    {
        Log::info('project_public_comment.accept.start', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
        ]);

        if (! $user->hasRole(SystemRole::Admin->value)) {
            Log::warning('project_public_comment.accept.rejected_permission', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Tylko administrator może zaakceptować komentarz.');
        }

        $comment->forceFill([
            'moderated' => true,
        ])->save();

        Log::info('project_public_comment.accept.success', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
        ]);

        return $comment->refresh();
    }
}

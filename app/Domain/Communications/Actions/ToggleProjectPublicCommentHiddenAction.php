<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Models\ProjectPublicComment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class ToggleProjectPublicCommentHiddenAction
{
    public function execute(ProjectPublicComment $comment, User $user): ProjectPublicComment
    {
        Log::info('project_public_comment.hide.toggle.start', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
            'current_hidden' => $comment->hidden,
        ]);

        if ($comment->created_by_id !== $user->id) {
            Log::warning('project_public_comment.hide.toggle.rejected_permission', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Można ukryć albo przywrócić tylko własny komentarz.');
        }

        $comment->forceFill([
            'hidden' => ! $comment->hidden,
        ])->save();

        Log::info('project_public_comment.hide.toggle.success', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
            'hidden' => $comment->hidden,
        ]);

        return $comment->refresh();
    }
}

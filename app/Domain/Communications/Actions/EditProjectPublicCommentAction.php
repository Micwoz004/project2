<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Models\ProjectPublicComment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class EditProjectPublicCommentAction
{
    private const MAX_CONTENT_LENGTH = 200;

    public function execute(ProjectPublicComment $comment, User $user, string $content): ProjectPublicComment
    {
        Log::info('project_public_comment.edit.start', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
        ]);

        if ($comment->created_by_id !== $user->id) {
            Log::warning('project_public_comment.edit.rejected_permission', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Można edytować tylko własny komentarz.');
        }

        $trimmedContent = trim($content);

        if ($trimmedContent === '') {
            Log::warning('project_public_comment.edit.rejected_empty_content', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Treść komentarza nie może być pusta.');
        }

        if (mb_strlen($trimmedContent) > self::MAX_CONTENT_LENGTH) {
            Log::warning('project_public_comment.edit.rejected_content_length', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'user_id' => $user->id,
                'limit' => self::MAX_CONTENT_LENGTH,
            ]);

            throw new DomainException('Treść komentarza nie może przekraczać 200 znaków.');
        }

        $comment->forceFill([
            'content' => $trimmedContent,
        ])->save();

        Log::info('project_public_comment.edit.success', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
        ]);

        return $comment->refresh();
    }
}

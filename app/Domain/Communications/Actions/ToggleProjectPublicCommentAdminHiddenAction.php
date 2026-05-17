<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class ToggleProjectPublicCommentAdminHiddenAction
{
    public function __construct(
        private readonly QueueProjectNotificationAction $queueProjectNotification,
    ) {}

    public function execute(ProjectPublicComment $comment, User $user): ProjectPublicComment
    {
        Log::info('project_public_comment.admin_hide.toggle.start', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
            'current_admin_hidden' => $comment->admin_hidden,
        ]);

        if (! $user->hasRole(SystemRole::Admin->value)) {
            Log::warning('project_public_comment.admin_hide.toggle.rejected_permission', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Tylko administrator może ukryć komentarz administracyjnie.');
        }

        $comment->forceFill([
            'admin_hidden' => ! $comment->admin_hidden,
        ])->save();

        if ($comment->admin_hidden) {
            $this->notifyCommentCreator($comment, $user);
        }

        Log::info('project_public_comment.admin_hide.toggle.success', [
            'comment_id' => $comment->id,
            'project_id' => $comment->project_id,
            'user_id' => $user->id,
            'admin_hidden' => $comment->admin_hidden,
        ]);

        return $comment->refresh();
    }

    private function notifyCommentCreator(ProjectPublicComment $comment, User $admin): void
    {
        $project = $comment->project;
        $creator = $comment->creator;

        if (! $project instanceof Project || ! $creator instanceof User || trim((string) $creator->email) === '') {
            Log::info('project_public_comment.admin_hide.notification_skipped', [
                'comment_id' => $comment->id,
                'project_id' => $comment->project_id,
                'admin_id' => $admin->id,
            ]);

            return;
        }

        $this->queueProjectNotification->execute(
            $project,
            $admin,
            $creator,
            $creator->email,
            ProjectNotificationTemplate::PublicCommentAdminHidden,
        );
    }
}

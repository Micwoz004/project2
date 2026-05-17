<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class AddProjectPublicCommentAction
{
    private const MAX_CONTENT_LENGTH = 200;

    public function __construct(
        private readonly QueueProjectNotificationAction $queueProjectNotification,
    ) {}

    public function execute(Project $project, User $user, string $content, ?ProjectPublicComment $parent = null): ProjectPublicComment
    {
        Log::info('project_public_comment.add.start', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
        ]);

        if (! $user->hasRole(SystemRole::Applicant->value)) {
            Log::warning('project_public_comment.add.rejected_permission', [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Brak uprawnień do dodania komentarza publicznego.');
        }

        $trimmedContent = $this->validateContent($project->id, $user->id, $content);

        if ($parent instanceof ProjectPublicComment && $parent->project_id !== $project->id) {
            Log::warning('project_public_comment.add.rejected_parent_project', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'parent_id' => $parent->id,
            ]);

            throw new DomainException('Komentarz nadrzędny należy do innego projektu.');
        }

        $comment = ProjectPublicComment::query()->create([
            'project_id' => $project->id,
            'parent_id' => $parent?->id,
            'created_by_id' => $user->id,
            'content' => $trimmedContent,
            'moderated' => true,
        ]);

        $this->notifyProjectCreator($project, $user, $trimmedContent);

        Log::info('project_public_comment.add.success', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);

        return $comment;
    }

    private function validateContent(int $projectId, int $userId, string $content): string
    {
        $trimmedContent = trim($content);

        if ($trimmedContent === '') {
            Log::warning('project_public_comment.add.rejected_empty_content', [
                'project_id' => $projectId,
                'user_id' => $userId,
            ]);

            throw new DomainException('Treść komentarza nie może być pusta.');
        }

        if (mb_strlen($trimmedContent) > self::MAX_CONTENT_LENGTH) {
            Log::warning('project_public_comment.add.rejected_content_length', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'limit' => self::MAX_CONTENT_LENGTH,
            ]);

            throw new DomainException('Treść komentarza nie może przekraczać 200 znaków.');
        }

        return $trimmedContent;
    }

    private function notifyProjectCreator(Project $project, User $user, string $content): void
    {
        $creator = $project->creator;

        if (! $creator instanceof User || trim((string) $creator->email) === '') {
            Log::info('project_public_comment.add.notification_skipped', [
                'project_id' => $project->id,
                'commenter_id' => $user->id,
            ]);

            return;
        }

        $this->queueProjectNotification->execute(
            $project,
            $user,
            $creator,
            $creator->email,
            ProjectNotificationTemplate::PublicCommentAdded,
            ['comment' => $content],
        );
    }
}

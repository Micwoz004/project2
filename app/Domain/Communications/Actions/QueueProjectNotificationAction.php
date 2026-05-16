<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Domain\Communications\Jobs\SendProjectNotificationJob;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class QueueProjectNotificationAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(
        Project $project,
        ?User $creator,
        ?User $recipient,
        string $recipientEmail,
        ProjectNotificationTemplate $template,
        array $context = [],
    ): ProjectNotification {
        Log::info('project_notification.queue.start', [
            'project_id' => $project->id,
            'creator_id' => $creator?->id,
            'recipient_id' => $recipient?->id,
            'template' => $template->value,
        ]);

        if (trim($recipientEmail) === '') {
            Log::warning('project_notification.queue.rejected_missing_email', [
                'project_id' => $project->id,
                'creator_id' => $creator?->id,
                'recipient_id' => $recipient?->id,
                'template' => $template->value,
            ]);

            throw new DomainException('Adres e-mail odbiorcy jest wymagany.');
        }

        $notification = ProjectNotification::query()->create([
            'project_id' => $project->id,
            'created_by_id' => $creator?->id,
            'sent_to_user_id' => $recipient?->id,
            'author_email' => $recipientEmail,
            'subject' => $template->subject($project, $context),
            'body' => $template->body($project, $context),
            'sent_at' => now(),
        ]);

        SendProjectNotificationJob::dispatch($notification->id);

        Log::info('project_notification.queue.success', [
            'project_id' => $project->id,
            'creator_id' => $creator?->id,
            'recipient_id' => $recipient?->id,
            'notification_id' => $notification->id,
            'template' => $template->value,
        ]);

        return $notification;
    }
}

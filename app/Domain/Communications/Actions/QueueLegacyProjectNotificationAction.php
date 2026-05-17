<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Enums\LegacyCommunicationTrigger;
use App\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class QueueLegacyProjectNotificationAction
{
    public function __construct(
        private readonly QueueProjectNotificationAction $queueProjectNotification,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(
        Project $project,
        ?User $creator,
        ?User $recipient,
        string $recipientEmail,
        LegacyCommunicationTrigger $trigger,
        array $context = [],
    ): ProjectNotification {
        Log::info('legacy_project_notification.queue.start', [
            'project_id' => $project->id,
            'creator_id' => $creator?->id,
            'recipient_id' => $recipient?->id,
            'trigger' => $trigger->value,
        ]);

        if ($trigger->channel() !== 'mail') {
            Log::warning('legacy_project_notification.queue.rejected_channel', [
                'project_id' => $project->id,
                'trigger' => $trigger->value,
                'channel' => $trigger->channel(),
            ]);

            throw new DomainException('Trigger legacy wymaga kanału innego niż e-mail.');
        }

        $template = $trigger->projectTemplate();

        if (! $template instanceof ProjectNotificationTemplate) {
            Log::warning('legacy_project_notification.queue.rejected_missing_template', [
                'project_id' => $project->id,
                'trigger' => $trigger->value,
            ]);

            throw new DomainException('Trigger legacy nie ma szablonu powiadomienia projektu.');
        }

        $notification = $this->queueProjectNotification->execute(
            $project,
            $creator,
            $recipient,
            $recipientEmail,
            $template,
            [
                ...$context,
                'legacy_trigger' => $trigger->value,
            ],
        );

        Log::info('legacy_project_notification.queue.success', [
            'project_id' => $project->id,
            'notification_id' => $notification->id,
            'trigger' => $trigger->value,
            'template' => $template->value,
        ]);

        return $notification;
    }
}

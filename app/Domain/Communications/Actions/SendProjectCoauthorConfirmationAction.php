<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Projects\Models\ProjectCoauthor;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendProjectCoauthorConfirmationAction
{
    public function __construct(
        private readonly QueueProjectNotificationAction $queueProjectNotification,
    ) {}

    public function execute(ProjectCoauthor $coauthor, ?User $creator = null): ?ProjectNotification
    {
        Log::info('project.coauthor.confirmation.send.start', [
            'project_id' => $coauthor->project_id,
            'coauthor_id' => $coauthor->id,
            'creator_id' => $creator?->id,
        ]);

        if (trim((string) $coauthor->email) === '') {
            Log::info('project.coauthor.confirmation.send.skipped_missing_email', [
                'project_id' => $coauthor->project_id,
                'coauthor_id' => $coauthor->id,
            ]);

            return null;
        }

        if (trim((string) $coauthor->hash) === '') {
            $coauthor->forceFill([
                'hash' => $this->legacyHash($coauthor),
            ])->save();
        }

        $notification = $this->queueProjectNotification->execute(
            $coauthor->project,
            $creator,
            null,
            (string) $coauthor->email,
            ProjectNotificationTemplate::CoauthorConfirmation,
            [
                'confirm_link' => route('public.coauthors.confirm', [
                    'email' => $coauthor->email,
                    'hash' => $coauthor->hash,
                ]),
            ],
        );

        Log::info('project.coauthor.confirmation.send.success', [
            'project_id' => $coauthor->project_id,
            'coauthor_id' => $coauthor->id,
            'notification_id' => $notification->id,
        ]);

        return $notification;
    }

    private function legacyHash(ProjectCoauthor $coauthor): string
    {
        return md5(now()->timestamp.$coauthor->project_id.$coauthor->email);
    }
}

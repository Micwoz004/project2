<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Models\CorrespondenceMessage;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class SendProjectCorrespondenceMessageAction
{
    public function execute(Project $project, User $sender, ?User $recipient, string $messageText): CorrespondenceMessage
    {
        Log::info('correspondence_message.send.start', [
            'project_id' => $project->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient?->id,
        ]);

        if (! $this->canCorrespond($project, $sender)) {
            Log::warning('correspondence_message.send.rejected_permission', [
                'project_id' => $project->id,
                'sender_id' => $sender->id,
            ]);

            throw new DomainException('Brak uprawnień do wysłania wiadomości.');
        }

        if (trim($messageText) === '') {
            Log::warning('correspondence_message.send.rejected_empty_content', [
                'project_id' => $project->id,
                'sender_id' => $sender->id,
            ]);

            throw new DomainException('Treść wiadomości nie może być pusta.');
        }

        $message = CorrespondenceMessage::query()->create([
            'project_id' => $project->id,
            'user_id' => $recipient?->id,
            'message_text' => $messageText,
            'is_read' => false,
        ]);

        Log::info('correspondence_message.send.success', [
            'project_id' => $project->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient?->id,
            'message_id' => $message->id,
        ]);

        return $message;
    }

    private function canCorrespond(Project $project, User $user): bool
    {
        return $project->creator_id === $user->id
            || $user->can('projects.manage')
            || $user->can('projects.verify')
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}

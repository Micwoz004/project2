<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Models\CorrespondenceMessage;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class MarkCorrespondenceMessageReadAction
{
    public function execute(CorrespondenceMessage $message, User $user): CorrespondenceMessage
    {
        Log::info('correspondence_message.mark_read.start', [
            'message_id' => $message->id,
            'project_id' => $message->project_id,
            'user_id' => $user->id,
        ]);

        if (! $this->canRead($message, $user)) {
            Log::warning('correspondence_message.mark_read.rejected_permission', [
                'message_id' => $message->id,
                'project_id' => $message->project_id,
                'user_id' => $user->id,
            ]);

            throw new DomainException('Brak uprawnień do oznaczenia wiadomości jako przeczytanej.');
        }

        $message->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();

        Log::info('correspondence_message.mark_read.success', [
            'message_id' => $message->id,
            'project_id' => $message->project_id,
            'user_id' => $user->id,
        ]);

        return $message->refresh();
    }

    private function canRead(CorrespondenceMessage $message, User $user): bool
    {
        return $message->user_id === $user->id
            || $user->can('projects.manage')
            || $user->can('projects.verify')
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}

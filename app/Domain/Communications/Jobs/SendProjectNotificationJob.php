<?php

namespace App\Domain\Communications\Jobs;

use App\Domain\Communications\Models\MailLog;
use App\Domain\Communications\Models\ProjectNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendProjectNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $notificationId,
    ) {}

    public function handle(): void
    {
        Log::info('project_notification.send.start', [
            'notification_id' => $this->notificationId,
        ]);

        $notification = ProjectNotification::query()->findOrFail($this->notificationId);

        Mail::raw($notification->body, function (Message $message) use ($notification): void {
            $message
                ->to($notification->author_email)
                ->subject($notification->subject);
        });

        MailLog::query()->create([
            'created_by_id' => $notification->created_by_id,
            'email' => $notification->author_email,
            'subject' => $notification->subject,
            'content' => $notification->body,
            'controller' => 'notification',
            'action' => 'sendProjectNotification',
            'sent_at' => now(),
        ]);

        Log::info('project_notification.send.success', [
            'notification_id' => $notification->id,
            'project_id' => $notification->project_id,
        ]);
    }
}

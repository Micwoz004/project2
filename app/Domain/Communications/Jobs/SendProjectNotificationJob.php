<?php

namespace App\Domain\Communications\Jobs;

use App\Domain\Communications\Enums\ProjectNotificationTemplate;
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

        $notification = ProjectNotification::query()
            ->with(['project.area', 'project.category', 'project.categories', 'project.budgetEdition'])
            ->findOrFail($this->notificationId);
        $template = $notification->template instanceof ProjectNotificationTemplate
            ? $notification->template
            : ProjectNotificationTemplate::tryFrom((string) $notification->template);

        Mail::send([
            'html' => $template?->htmlView() ?? 'mail.project-notification',
            'text' => $template?->textView() ?? 'mail.project-notification-text',
        ], [
            'notification' => $notification,
            'project' => $notification->project,
        ], function (Message $message) use ($notification): void {
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

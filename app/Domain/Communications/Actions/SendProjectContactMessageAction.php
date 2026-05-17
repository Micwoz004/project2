<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Jobs\SendProjectNotificationJob;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Projects\Models\Project;
use DomainException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SendProjectContactMessageAction
{
    public function execute(Project $project, string $senderEmail, string $subject, string $content): ProjectNotification
    {
        Log::info('project.contact_message.send.start', [
            'project_id' => $project->id,
            'creator_id' => $project->creator_id,
            'sender_email_hash' => hash('sha256', mb_strtolower(trim($senderEmail))),
        ]);

        $data = $this->validatedData($senderEmail, $subject, $content, $project->id);
        $project->loadMissing('creator');
        $recipientEmail = trim((string) $project->creator?->email);

        if ($recipientEmail === '') {
            Log::warning('project.contact_message.send.rejected_missing_creator_email', [
                'project_id' => $project->id,
                'creator_id' => $project->creator_id,
            ]);

            throw new DomainException('Autor projektu nie podał adresu e-mail');
        }

        $notification = ProjectNotification::query()->create([
            'project_id' => $project->id,
            'created_by_id' => null,
            'sent_to_user_id' => $project->creator_id,
            'author_email' => $recipientEmail,
            'subject' => $data['subject'],
            'body' => $this->legacyContent($data['sender_email'], $data['content']),
            'sent_at' => now(),
        ]);

        SendProjectNotificationJob::dispatch($notification->id);

        Log::info('project.contact_message.send.success', [
            'project_id' => $project->id,
            'creator_id' => $project->creator_id,
            'notification_id' => $notification->id,
        ]);

        return $notification;
    }

    /**
     * @return array{sender_email: string, subject: string, content: string}
     */
    private function validatedData(string $senderEmail, string $subject, string $content, int $projectId): array
    {
        $validator = Validator::make([
            'sender_email' => $senderEmail,
            'subject' => $subject,
            'content' => $content,
        ], [
            'sender_email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:250'],
            'content' => ['required', 'string', 'max:3000'],
        ], [
            'required' => 'Pole :attribute jest wymagane.',
            'email' => 'Nieprawidłowy adres e-mail.',
            'max' => 'Pole :attribute jest zbyt długie.',
        ], [
            'sender_email' => 'Twój adres e-mail',
            'subject' => 'Temat',
            'content' => 'Treść',
        ]);

        if ($validator->fails()) {
            Log::warning('project.contact_message.send.rejected_validation', [
                'project_id' => $projectId,
                'errors_count' => $validator->errors()->count(),
            ]);

            throw new DomainException((string) $validator->errors()->first());
        }

        return [
            'sender_email' => trim((string) $validator->validated()['sender_email']),
            'subject' => trim((string) $validator->validated()['subject']),
            'content' => trim((string) $validator->validated()['content']),
        ];
    }

    private function legacyContent(string $senderEmail, string $content): string
    {
        return 'Otrzymałeś/aś wiadomość od '.$senderEmail.'. To wiadomość wysłana z systemu obsługującego Szczecińskiego Budżetu Obywatelskiego 2024. Aby odpowiedzieć wyślij wiadomość do '.$senderEmail.'. <br /><br />'.$content;
    }
}

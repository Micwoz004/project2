<?php

namespace App\Domain\Communications\Enums;

use App\Domain\Projects\Models\Project;
use Illuminate\Support\Arr;

enum ProjectNotificationTemplate: string
{
    case ProjectSubmitted = 'project_submitted';
    case ProjectPublished = 'project_published';
    case OfficialNewProject = 'official_new_project';
    case CorrespondenceMessage = 'correspondence_message';
    case FormalCorrection = 'formal_correction';
    case VerificationPressure = 'verification_pressure';
    case ProjectStatusChanged = 'project_status_changed';
    case PublicCommentAdded = 'public_comment_added';
    case PublicCommentAdminHidden = 'public_comment_admin_hidden';
    case CoauthorConfirmation = 'coauthor_confirmation';

    /**
     * @param  array<string, mixed>  $context
     */
    public function subject(Project $project, array $context = []): string
    {
        return match ($this) {
            self::ProjectSubmitted => 'Twój projekt został przyjęty do weryfikacji '.$this->projectNumber($project),
            self::ProjectPublished => 'Twój projekt został opublikowany '.$this->projectNumber($project),
            self::OfficialNewProject => 'Nowy projekt czeka na weryfikację '.$this->projectNumber($project),
            self::CorrespondenceMessage => 'Nowa wiadomość dotycząca projektu '.$this->projectNumber($project),
            self::FormalCorrection => 'Wezwanie do korekty projektu '.$this->projectNumber($project),
            self::VerificationPressure => 'Monit weryfikacyjny projektu '.$this->projectNumber($project),
            self::ProjectStatusChanged => 'Zmiana statusu projektu '.$this->projectNumber($project),
            self::PublicCommentAdded => 'Nowy komentarz dotyczący projektu '.$this->projectNumber($project),
            self::PublicCommentAdminHidden => 'Komentarz został ukryty przy projekcie '.$this->projectNumber($project),
            self::CoauthorConfirmation => 'Potwierdzenie współautorstwa projektu '.$this->projectNumber($project),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function body(Project $project, array $context = []): string
    {
        return match ($this) {
            self::ProjectSubmitted => implode(PHP_EOL, [
                'Projekt jest już w systemie:',
                $project->title,
                '',
                'Wniosek został zapisany i przekazany do weryfikacji formalnej.',
            ]),
            self::ProjectPublished => implode(PHP_EOL, [
                'Projekt został opublikowany:',
                $project->title,
                '',
                'Projekt jest dostępny na publicznej liście projektów.',
            ]),
            self::OfficialNewProject => implode(PHP_EOL, [
                'Nowy projekt do weryfikacji:',
                $project->title,
                '',
                'Sprawdź kompletność danych i rozpocznij obsługę weryfikacji.',
            ]),
            self::CorrespondenceMessage => implode(PHP_EOL, [
                'W systemie SBO dodano nową wiadomość dotyczącą projektu:',
                $project->title,
                '',
                trim((string) Arr::get($context, 'message', '')),
            ]),
            self::FormalCorrection => implode(PHP_EOL, [
                'Projekt wymaga korekty:',
                $project->title,
                '',
                trim((string) Arr::get($context, 'notes', '')),
            ]),
            self::VerificationPressure => implode(PHP_EOL, [
                'Projekt wymaga obsługi weryfikacyjnej:',
                $project->title,
                '',
                trim((string) Arr::get($context, 'notes', '')),
            ]),
            self::ProjectStatusChanged => implode(PHP_EOL, [
                'Zmieniono status projektu:',
                $project->title,
                'Status: '.trim((string) Arr::get($context, 'status', $project->status->publicLabel())),
            ]),
            self::PublicCommentAdded => implode(PHP_EOL, [
                'W systemie SBO dodano nowy komentarz dotyczący projektu:',
                $project->title,
                '',
                trim((string) Arr::get($context, 'comment', '')),
            ]),
            self::PublicCommentAdminHidden => implode(PHP_EOL, [
                'Administrator ukrył komentarz dotyczący projektu:',
                $project->title,
            ]),
            self::CoauthorConfirmation => implode(PHP_EOL, [
                'Zostałaś/-eś wskazana/-y jako współautor projektu:',
                $project->title,
                '',
                'Potwierdź status współautora:',
                trim((string) Arr::get($context, 'confirm_link', '')),
            ]),
        };
    }

    public function htmlView(): string
    {
        return match ($this) {
            self::ProjectSubmitted => 'mail.resident-project-submitted',
            self::ProjectPublished => 'mail.resident-project-published',
            self::OfficialNewProject => 'mail.official-new-project',
            self::FormalCorrection => 'mail.resident-project-needs-correction',
            self::VerificationPressure => 'mail.official-verification-deadline',
            default => 'mail.project-notification',
        };
    }

    public function textView(): string
    {
        return match ($this) {
            self::ProjectSubmitted => 'mail.resident-project-submitted-text',
            self::ProjectPublished => 'mail.resident-project-published-text',
            self::OfficialNewProject => 'mail.official-new-project-text',
            self::FormalCorrection => 'mail.resident-project-needs-correction-text',
            self::VerificationPressure => 'mail.official-verification-deadline-text',
            default => 'mail.project-notification-text',
        };
    }

    private function projectNumber(Project $project): string
    {
        $number = $project->number_drawn ?? $project->number;

        if ($number === null) {
            return '#'.$project->id;
        }

        return (string) $number;
    }
}

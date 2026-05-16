<?php

namespace App\Domain\Communications\Enums;

use App\Domain\Projects\Models\Project;
use Illuminate\Support\Arr;

enum ProjectNotificationTemplate: string
{
    case CorrespondenceMessage = 'correspondence_message';
    case FormalCorrection = 'formal_correction';
    case VerificationPressure = 'verification_pressure';
    case ProjectStatusChanged = 'project_status_changed';

    /**
     * @param  array<string, mixed>  $context
     */
    public function subject(Project $project, array $context = []): string
    {
        return match ($this) {
            self::CorrespondenceMessage => 'Nowa wiadomość dotycząca projektu '.$this->projectNumber($project),
            self::FormalCorrection => 'Wezwanie do korekty projektu '.$this->projectNumber($project),
            self::VerificationPressure => 'Monit weryfikacyjny projektu '.$this->projectNumber($project),
            self::ProjectStatusChanged => 'Zmiana statusu projektu '.$this->projectNumber($project),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function body(Project $project, array $context = []): string
    {
        return match ($this) {
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
        };
    }

    private function projectNumber(Project $project): string
    {
        if ($project->number === null) {
            return '#'.$project->id;
        }

        return (string) $project->number;
    }
}

<?php

namespace App\Domain\Projects\Services;

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Models\Project;
use DomainException;
use Illuminate\Support\Facades\Log;

class ProjectSubmissionValidator
{
    public function __construct(
        private readonly ProjectLifecycleService $lifecycle,
    ) {}

    public function assertCanSubmit(Project $project): void
    {
        if (! $this->lifecycle->canSubmit($project)) {
            Log::warning('project.submit.rejected_status', [
                'project_id' => $project->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Projekt nie jest w stanie pozwalającym na złożenie.');
        }

        if (! $project->costItems()->exists()) {
            Log::warning('project.submit.rejected_missing_costs', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Projekt musi mieć co najmniej jedną pozycję kosztorysu.');
        }

        $hasSupportListFile = $project->files()
            ->where('type', ProjectFileType::SupportList->value)
            ->exists();

        if (! $project->is_support_list && ! $hasSupportListFile) {
            Log::warning('project.submit.rejected_missing_support_list', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Projekt musi mieć potwierdzoną listę poparcia.');
        }

        foreach ($this->urlCheckedFields($project) as $field => $value) {
            if (preg_match('/(?:https?:\/\/|www\.)/i', $value) === 1) {
                Log::warning('project.submit.rejected_url_in_text', [
                    'project_id' => $project->id,
                    'field' => $field,
                ]);

                throw new DomainException('Pola opisowe projektu nie mogą zawierać adresów URL.');
            }
        }
    }

    private function urlCheckedFields(Project $project): array
    {
        return [
            'title' => $project->title,
            'description' => (string) $project->description,
            'goal' => (string) $project->goal,
            'localization' => (string) $project->localization,
            'argumentation' => (string) $project->argumentation,
            'availability' => (string) $project->availability,
            'recipients' => (string) $project->recipients,
            'free_of_charge' => (string) $project->free_of_charge,
        ];
    }
}

<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartCorrectionAction
{
    /**
     * @param  list<ProjectCorrectionField>  $allowedFields
     */
    public function execute(
        Project $project,
        User $actor,
        array $allowedFields,
        ?string $notes = null,
        ?Carbon $deadline = null,
    ): ProjectCorrection {
        Log::info('project.correction.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => $project->status->value,
        ]);

        if ($project->status === ProjectStatus::WorkingCopy) {
            Log::warning('project.correction.rejected_working_copy', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Nie można wezwać do korekty kopii roboczej.');
        }

        if ($allowedFields === []) {
            Log::warning('project.correction.rejected_no_fields', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Korekta musi wskazywać co najmniej jedno pole.');
        }

        if ($project->corrections()->where('correction_done', false)->where('correction_deadline', '>', Carbon::now())->exists()) {
            Log::warning('project.correction.rejected_active_correction_exists', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Projekt ma już aktywne wezwanie do korekty.');
        }

        return DB::transaction(function () use ($project, $actor, $allowedFields, $notes, $deadline): ProjectCorrection {
            $correctionDeadline = $deadline ?? $this->defaultLegacyDeadline();

            $correction = $project->corrections()->create([
                'creator_id' => $actor->id,
                'allowed_fields' => array_map(
                    static fn (ProjectCorrectionField $field): string => $field->value,
                    $allowedFields,
                ),
                'notes' => $notes,
                'correction_deadline' => $correctionDeadline,
            ]);

            $project->forceFill([
                'need_correction' => true,
                'correction_no' => ((int) $project->correction_no) + 1,
                'correction_start_time' => Carbon::now(),
                'correction_end_time' => $correctionDeadline,
            ])->save();

            Log::info('project.correction.started', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'correction_id' => $correction->id,
                'correction_no' => $project->correction_no,
            ]);

            return $correction;
        });
    }

    private function defaultLegacyDeadline(): Carbon
    {
        return Carbon::now()->addWeekdays(5)->addDay()->startOfDay();
    }
}

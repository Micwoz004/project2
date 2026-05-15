<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Services\ProjectLifecycleService;
use App\Domain\Projects\Services\ProjectSubmissionValidator;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyCorrectionAction
{
    public function __construct(
        private readonly ProjectLifecycleService $lifecycle,
        private readonly ProjectSubmissionValidator $validator,
        private readonly RecordProjectVersionAction $recordProjectVersion,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Project $project, User $actor, array $attributes): Project
    {
        Log::info('project.correction.apply.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => $project->status->value,
        ]);

        if (! $this->lifecycle->canApplicantEdit($project)) {
            Log::warning('project.correction.apply.rejected_closed_window', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Projekt nie jest w aktywnym oknie korekty.');
        }

        $correction = $this->activeCorrection($project);
        $allowedAttributes = $this->filterAllowedAttributes($attributes, $correction);

        if ($allowedAttributes === []) {
            Log::warning('project.correction.apply.rejected_no_allowed_fields', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'correction_id' => $correction->id,
            ]);

            throw new DomainException('Przekazane pola nie są dopuszczone do korekty.');
        }

        return DB::transaction(function () use ($project, $actor, $correction, $allowedAttributes): Project {
            $project->forceFill($allowedAttributes)->save();
            $this->validator->assertCanSubmit($project);

            $correction->forceFill([
                'correction_done' => true,
            ])->save();

            $project->forceFill([
                'need_correction' => false,
                'correction_start_time' => null,
                'correction_end_time' => null,
            ])->save();

            $this->recordProjectVersion->execute($project, $actor);

            Log::info('project.correction.apply.success', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'correction_id' => $correction->id,
                'status' => $project->status->value,
            ]);

            return $project->refresh();
        });
    }

    private function activeCorrection(Project $project): ProjectCorrection
    {
        $correction = $project->corrections()
            ->where('correction_done', false)
            ->where('correction_deadline', '>', Carbon::now())
            ->latest()
            ->first();

        if (! $correction instanceof ProjectCorrection) {
            Log::warning('project.correction.apply.rejected_missing_correction', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Nie znaleziono aktywnej korekty.');
        }

        return $correction;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filterAllowedAttributes(array $attributes, ProjectCorrection $correction): array
    {
        $allowedColumns = array_intersect(
            ProjectCorrectionField::editableProjectColumns(),
            $correction->allowed_fields,
        );

        return array_intersect_key($attributes, array_flip($allowedColumns));
    }
}

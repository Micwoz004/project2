<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Services\ProjectSubmissionValidator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitProjectAction
{
    public function __construct(
        private readonly ProjectSubmissionValidator $validator,
        private readonly RecordProjectVersionAction $recordProjectVersion,
    ) {}

    public function execute(Project $project, ?User $actor = null): Project
    {
        Log::info('project.submit.start', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
            'status' => $project->status->value,
        ]);

        $this->validator->assertCanSubmit($project);

        return DB::transaction(function () use ($project, $actor): Project {
            $nextNumber = Project::query()
                ->where('budget_edition_id', $project->budget_edition_id)
                ->where('project_area_id', $project->project_area_id)
                ->max('number');

            $project->forceFill([
                'status' => ProjectStatus::Submitted,
                'submitted_at' => now(),
                'number' => ((int) $nextNumber) + 1,
            ])->save();

            $this->recordProjectVersion->execute($project, $actor);

            Log::info('project.submit.success', [
                'project_id' => $project->id,
                'actor_id' => $actor?->id,
                'status' => ProjectStatus::Submitted->value,
            ]);

            return $project->refresh();
        });
    }
}

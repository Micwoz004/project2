<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Actions\StartCorrectionAction;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestFormalCorrectionAction
{
    public function __construct(
        private readonly StartCorrectionAction $startCorrection,
    ) {}

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
        Log::info('verification.formal.correction.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => $project->status->value,
        ]);

        if (! in_array($project->status, [ProjectStatus::Submitted, ProjectStatus::DuringFormalVerification], true)) {
            Log::warning('verification.formal.correction.rejected_status', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Korektę formalną można uruchomić tylko dla projektu złożonego albo w weryfikacji formalnej.');
        }

        return DB::transaction(function () use ($project, $actor, $allowedFields, $notes, $deadline): ProjectCorrection {
            $project->forceFill([
                'status' => ProjectStatus::DuringFormalVerification,
            ])->save();

            $correction = $this->startCorrection->execute($project, $actor, $allowedFields, $notes, $deadline);

            Log::info('verification.formal.correction.success', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'correction_id' => $correction->id,
            ]);

            return $correction;
        });
    }
}

<?php

namespace App\Products\CivicBudget\Domain\Verification\Actions;

use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class BeginFormalVerificationAction
{
    public function execute(Project $project, User $actor): Project
    {
        Log::info('verification.formal.begin.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => $project->status->value,
        ]);

        if ($project->status !== ProjectStatus::Submitted) {
            Log::warning('verification.formal.begin.rejected_status', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Weryfikację formalną można rozpocząć tylko dla projektu złożonego.');
        }

        $project->forceFill([
            'status' => ProjectStatus::DuringFormalVerification,
        ])->save();

        Log::info('verification.formal.begin.success', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => ProjectStatus::DuringFormalVerification->value,
        ]);

        return $project->refresh();
    }
}

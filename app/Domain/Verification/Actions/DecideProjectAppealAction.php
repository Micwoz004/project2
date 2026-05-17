<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Verification\Enums\ProjectAppealFirstDecision;
use App\Domain\Verification\Models\ProjectAppeal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DecideProjectAppealAction
{
    public function execute(ProjectAppeal $appeal, User $actor, ProjectAppealFirstDecision $decision): ProjectAppeal
    {
        Log::info('project.appeal.decision.start', [
            'appeal_id' => $appeal->id,
            'project_id' => $appeal->project_id,
            'actor_id' => $actor->id,
            'decision' => $decision->value,
        ]);

        if ($decision === ProjectAppealFirstDecision::Pending) {
            Log::warning('project.appeal.decision.rejected_pending', [
                'appeal_id' => $appeal->id,
                'project_id' => $appeal->project_id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Decyzja wstępna odwołania musi być akceptacją albo odrzuceniem.');
        }

        return DB::transaction(function () use ($appeal, $actor, $decision): ProjectAppeal {
            $appeal->forceFill([
                'first_decision' => $decision->value,
                'first_decision_created_at' => now(),
            ])->save();

            if ($decision === ProjectAppealFirstDecision::Accepted) {
                $appeal->project->forceFill([
                    'status' => ProjectStatus::FormallyVerified,
                ])->save();
            }

            Log::info('project.appeal.decision.success', [
                'appeal_id' => $appeal->id,
                'project_id' => $appeal->project_id,
                'actor_id' => $actor->id,
                'decision' => $decision->value,
                'project_status' => $appeal->project->status->value,
            ]);

            return $appeal->refresh();
        });
    }
}

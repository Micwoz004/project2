<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Models\FormalVerification;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompleteFormalVerificationAction
{
    /**
     * @param  array<string, mixed>  $answers
     */
    public function execute(
        Project $project,
        User $actor,
        bool $result,
        array $answers = [],
        ?string $resultComments = null,
    ): FormalVerification {
        Log::info('verification.formal.complete.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'result' => $result,
            'status' => $project->status->value,
        ]);

        $this->assertCanComplete($project, $result, $resultComments);

        return DB::transaction(function () use ($project, $actor, $result, $answers, $resultComments): FormalVerification {
            $nextStatus = $result ? ProjectStatus::FormallyVerified : ProjectStatus::RejectedFormally;

            $verification = FormalVerification::query()->updateOrCreate(
                ['project_id' => $project->id],
                [
                    'created_by_id' => $actor->id,
                    'modified_by_id' => $actor->id,
                    'status' => $nextStatus->value,
                    'result' => $result,
                    'result_comments' => $resultComments,
                    'answers' => $answers,
                ],
            );

            $project->forceFill([
                'status' => $nextStatus,
            ])->save();

            Log::info('verification.formal.complete.success', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'verification_id' => $verification->id,
                'status' => $nextStatus->value,
            ]);

            return $verification;
        });
    }

    private function assertCanComplete(Project $project, bool $result, ?string $resultComments): void
    {
        if (! in_array($project->status, [ProjectStatus::Submitted, ProjectStatus::DuringFormalVerification], true)) {
            Log::warning('verification.formal.complete.rejected_status', [
                'project_id' => $project->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Nie można zakończyć weryfikacji formalnej dla tego statusu projektu.');
        }

        if ($result && ! $project->is_support_list) {
            Log::warning('verification.formal.complete.rejected_missing_support_list', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Pozytywna weryfikacja formalna wymaga poprawnej listy poparcia.');
        }

        if (! $result && trim((string) $resultComments) === '') {
            Log::warning('verification.formal.complete.rejected_missing_negative_reason', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Negatywna weryfikacja formalna wymaga uzasadnienia.');
        }
    }
}

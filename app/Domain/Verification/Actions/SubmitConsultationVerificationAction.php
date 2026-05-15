<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Enums\VerificationCardStatus;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitConsultationVerificationAction
{
    /**
     * @param  array<string, mixed>  $answers
     */
    public function execute(
        Project $project,
        Department $department,
        User $actor,
        bool $result,
        array $answers = [],
        ?string $resultComments = null,
        bool $sent = true,
    ): ConsultationVerification {
        Log::info('verification.consultation.submit.start', [
            'project_id' => $project->id,
            'department_id' => $department->id,
            'actor_id' => $actor->id,
            'sent' => $sent,
            'result' => $result,
        ]);

        $this->assertCanSave($project, $department, $result, $resultComments, $sent);

        return DB::transaction(function () use ($project, $department, $actor, $result, $answers, $resultComments, $sent): ConsultationVerification {
            $cardStatus = $sent ? VerificationCardStatus::Sent : VerificationCardStatus::WorkingCopy;

            $verification = ConsultationVerification::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'department_id' => $department->id,
                    'created_by_id' => $actor->id,
                ],
                [
                    'modified_by_id' => $actor->id,
                    'status' => $cardStatus,
                    'result' => $result,
                    'result_comments' => $resultComments,
                    'answers' => $answers,
                    'sent_at' => $sent ? now() : null,
                ],
            );

            if ($sent) {
                $this->markAssignmentSent($project, $department, VerificationAssignmentType::Consultation);
            }

            Log::info('verification.consultation.submit.success', [
                'project_id' => $project->id,
                'department_id' => $department->id,
                'verification_id' => $verification->id,
                'card_status' => $cardStatus->value,
                'project_status' => $project->status->value,
            ]);

            return $verification;
        });
    }

    private function assertCanSave(Project $project, Department $department, bool $result, ?string $resultComments, bool $sent): void
    {
        if (! in_array($project->status, [ProjectStatus::SentForMeritVerification, ProjectStatus::DuringMeritVerification], true)) {
            Log::warning('verification.consultation.submit.rejected_status', [
                'project_id' => $project->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Konsultację można zapisać tylko w toku weryfikacji merytorycznej.');
        }

        if ($sent && ! $this->hasAssignment($project, $department, VerificationAssignmentType::Consultation)) {
            Log::warning('verification.consultation.submit.rejected_missing_assignment', [
                'project_id' => $project->id,
                'department_id' => $department->id,
            ]);

            throw new DomainException('Brak przydziału departamentu do konsultacji.');
        }

        if ($sent && ! $result && trim((string) $resultComments) === '') {
            Log::warning('verification.consultation.submit.rejected_missing_negative_reason', [
                'project_id' => $project->id,
                'department_id' => $department->id,
            ]);

            throw new DomainException('Negatywna konsultacja wymaga uzasadnienia.');
        }
    }

    private function hasAssignment(Project $project, Department $department, VerificationAssignmentType $type): bool
    {
        return $project->verificationAssignments()
            ->where('department_id', $department->id)
            ->where('type', $type->value)
            ->exists();
    }

    private function markAssignmentSent(Project $project, Department $department, VerificationAssignmentType $type): void
    {
        $project->verificationAssignments()
            ->where('department_id', $department->id)
            ->where('type', $type->value)
            ->update([
                'sent_at' => now(),
                'is_returned' => false,
            ]);
    }
}

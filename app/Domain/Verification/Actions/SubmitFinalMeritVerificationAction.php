<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Enums\VerificationCardStatus;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitFinalMeritVerificationAction
{
    public function __construct(
        private readonly RecordVerificationVersionAction $recordVerificationVersion,
    ) {}

    /**
     * @param  array<string, mixed>  $answers
     * @param  list<array{description: string, sum: int|float|string}>  $correctedCosts
     * @param  list<array{description: string, sum: int|float|string}>  $futureCosts
     */
    public function execute(
        Project $project,
        Department $department,
        User $actor,
        bool $result,
        array $answers = [],
        ?string $resultComments = null,
        array $correctedCosts = [],
        array $futureCosts = [],
        bool $sent = true,
    ): FinalMeritVerification {
        Log::info('verification.final.submit.start', [
            'project_id' => $project->id,
            'department_id' => $department->id,
            'actor_id' => $actor->id,
            'sent' => $sent,
            'result' => $result,
        ]);

        $futureCostsToSave = $this->futureCostsForAnswers($project, $answers, $futureCosts);

        $this->assertCanSave($project, $department, $result, $resultComments, $correctedCosts, $futureCostsToSave, $sent);

        return DB::transaction(function () use ($project, $department, $actor, $result, $answers, $resultComments, $correctedCosts, $futureCostsToSave, $sent): FinalMeritVerification {
            $cardStatus = $sent ? VerificationCardStatus::Sent : VerificationCardStatus::WorkingCopy;
            $projectStatus = $result ? ProjectStatus::MeritVerificationAccepted : ProjectStatus::MeritVerificationRejected;

            $verification = FinalMeritVerification::query()->updateOrCreate(
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
                    'answers' => [
                        ...$answers,
                        'correctedCost' => $this->normalizeCosts($correctedCosts),
                        'futureCost' => $this->normalizeCosts($futureCostsToSave),
                    ],
                    'sent_at' => $sent ? now() : null,
                ],
            );

            $snapshotAnswers = [
                ...$answers,
                'correctedCost' => $this->normalizeCosts($correctedCosts),
                'futureCost' => $this->normalizeCosts($futureCostsToSave),
            ];

            $this->recordVerificationVersion->execute(
                $verification,
                VerificationAssignmentType::MeritFinish,
                $actor,
                [
                    'project_id' => $project->id,
                    'department_id' => $department->id,
                    'status' => $cardStatus->value,
                    'result' => $result,
                    'result_comments' => $resultComments,
                    'answers' => $snapshotAnswers,
                    'sent_at' => $sent ? $verification->sent_at?->toDateTimeString() : null,
                ],
            );

            if ($sent) {
                $this->markAssignmentSent($project, $department, VerificationAssignmentType::MeritFinish);

                if ($this->allAssignmentsSent($project, VerificationAssignmentType::MeritFinish)) {
                    $project->forceFill([
                        'status' => $this->hasRejectedCard($project) ? ProjectStatus::MeritVerificationRejected : $projectStatus,
                    ])->save();
                } else {
                    $project->forceFill([
                        'status' => ProjectStatus::DuringMeritVerification,
                    ])->save();
                }
            }

            Log::info('verification.final.submit.success', [
                'project_id' => $project->id,
                'department_id' => $department->id,
                'verification_id' => $verification->id,
                'card_status' => $cardStatus->value,
                'project_status' => $project->status->value,
            ]);

            return $verification;
        });
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  list<array{description: string, sum: int|float|string}>  $futureCosts
     * @return list<array{description: string, sum: int|float|string}>
     */
    private function futureCostsForAnswers(Project $project, array $answers, array $futureCosts): array
    {
        if (! array_key_exists('hasAdditionalCosts', $answers)) {
            return $futureCosts;
        }

        $hasAdditionalCosts = (int) $answers['hasAdditionalCosts'];

        if (! in_array($hasAdditionalCosts, [0, 2], true)) {
            return $futureCosts;
        }

        if ($futureCosts !== []) {
            Log::info('verification.final.future_costs.skipped', [
                'project_id' => $project->id,
                'has_additional_costs' => $hasAdditionalCosts,
            ]);
        }

        return [];
    }

    /**
     * @param  list<array{description: string, sum: int|float|string}>  $correctedCosts
     * @param  list<array{description: string, sum: int|float|string}>  $futureCosts
     */
    private function assertCanSave(
        Project $project,
        Department $department,
        bool $result,
        ?string $resultComments,
        array $correctedCosts,
        array $futureCosts,
        bool $sent,
    ): void {
        if (! in_array($project->status, [ProjectStatus::SentForMeritVerification, ProjectStatus::DuringMeritVerification], true)) {
            Log::warning('verification.final.submit.rejected_status', [
                'project_id' => $project->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Końcową weryfikację merytoryczną można zapisać tylko po skierowaniu do weryfikacji merytorycznej.');
        }

        if ($sent && ! $this->hasAssignment($project, $department, VerificationAssignmentType::MeritFinish)) {
            Log::warning('verification.final.submit.rejected_missing_assignment', [
                'project_id' => $project->id,
                'department_id' => $department->id,
            ]);

            throw new DomainException('Brak przydziału departamentu do końcowej weryfikacji merytorycznej.');
        }

        if ($sent && ! $result && trim((string) $resultComments) === '') {
            Log::warning('verification.final.submit.rejected_missing_negative_reason', [
                'project_id' => $project->id,
                'department_id' => $department->id,
            ]);

            throw new DomainException('Negatywna końcowa weryfikacja merytoryczna wymaga uzasadnienia.');
        }

        if ($sent) {
            $this->assertCostsComplete($correctedCosts, 'Koszt składowej dla kosztów szacunkowych nie może być pusty');
            $this->assertCostsComplete($futureCosts, 'Koszt składowej dla kosztów w kolejnych latach nie może być pusty');
        }
    }

    /**
     * @param  list<array{description: string, sum: int|float|string}>  $costs
     */
    private function assertCostsComplete(array $costs, string $message): void
    {
        foreach ($costs as $cost) {
            if (trim((string) $cost['description']) === '' || trim((string) $cost['sum']) === '') {
                Log::warning('verification.final.submit.rejected_incomplete_cost');

                throw new DomainException($message);
            }
        }
    }

    /**
     * @param  list<array{description: string, sum: int|float|string}>  $costs
     * @return list<array{description: string, sum: float}>
     */
    private function normalizeCosts(array $costs): array
    {
        return array_map(
            static fn (array $cost): array => [
                'description' => trim($cost['description']),
                'sum' => (float) $cost['sum'],
            ],
            $costs,
        );
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

    private function allAssignmentsSent(Project $project, VerificationAssignmentType $type): bool
    {
        return ! $project->verificationAssignments()
            ->where('type', $type->value)
            ->whereNull('sent_at')
            ->exists();
    }

    private function hasRejectedCard(Project $project): bool
    {
        return FinalMeritVerification::query()
            ->where('project_id', $project->id)
            ->where('status', VerificationCardStatus::Sent)
            ->where('result', false)
            ->exists();
    }
}

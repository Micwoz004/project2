<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForwardFormalVerificationToInitialVerificationAction
{
    public function __construct(
        private readonly AssignVerificationDepartmentAction $assignVerificationDepartment,
    ) {}

    /**
     * @param  list<Department>  $departments
     */
    public function execute(
        Project $project,
        User $actor,
        array $departments,
        ?Carbon $deadline = null,
        ?string $notes = null,
    ): Project {
        Log::info('verification.formal.forward_initial.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => $project->status->value,
            'departments_count' => count($departments),
        ]);

        if ($project->status !== ProjectStatus::FormallyVerified) {
            Log::warning('verification.formal.forward_initial.rejected_status', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Do weryfikacji wstępnej można przekazać tylko projekt zweryfikowany formalnie.');
        }

        if ($departments === []) {
            Log::warning('verification.formal.forward_initial.rejected_no_departments', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Przekazanie do weryfikacji wstępnej wymaga co najmniej jednej jednostki.');
        }

        return DB::transaction(function () use ($project, $actor, $departments, $deadline, $notes): Project {
            foreach ($departments as $department) {
                $this->assignVerificationDepartment->execute(
                    $project,
                    $department,
                    VerificationAssignmentType::MeritInitial,
                    $deadline,
                    $notes,
                );
            }

            $project->forceFill([
                'need_pre_verification' => true,
                'status' => ProjectStatus::DuringInitialVerification,
            ])->save();

            Log::info('verification.formal.forward_initial.success', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'status' => ProjectStatus::DuringInitialVerification->value,
                'departments_count' => count($departments),
            ]);

            return $project->refresh();
        });
    }
}

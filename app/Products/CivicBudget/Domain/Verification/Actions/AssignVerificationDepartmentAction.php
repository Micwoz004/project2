<?php

namespace App\Products\CivicBudget\Domain\Verification\Actions;

use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Platform\Users\Models\Department;
use App\Products\CivicBudget\Domain\Verification\Enums\VerificationAssignmentType;
use App\Products\CivicBudget\Domain\Verification\Models\VerificationAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AssignVerificationDepartmentAction
{
    public function execute(
        Project $project,
        Department $department,
        VerificationAssignmentType $type,
        ?Carbon $deadline = null,
        ?string $notes = null,
    ): VerificationAssignment {
        Log::info('verification.assignment.create.start', [
            'project_id' => $project->id,
            'department_id' => $department->id,
            'type' => $type->value,
        ]);

        $assignment = VerificationAssignment::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'department_id' => $department->id,
                'type' => $type,
            ],
            [
                'deadline' => $deadline,
                'notes' => $notes,
                'is_returned' => false,
            ],
        );

        Log::info('verification.assignment.create.success', [
            'project_id' => $project->id,
            'department_id' => $department->id,
            'assignment_id' => $assignment->id,
            'type' => $type->value,
        ]);

        return $assignment;
    }
}

<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use Illuminate\Support\Carbon;

class ProjectLifecycleService
{
    public function canApplicantEdit(Project $project): bool
    {
        if ($project->status === ProjectStatus::WorkingCopy) {
            return true;
        }

        $now = Carbon::now();

        return $project->need_correction
            && $project->correction_start_time?->lessThanOrEqualTo($now) === true
            && $project->correction_end_time?->greaterThanOrEqualTo($now) === true;
    }

    public function canSubmit(Project $project): bool
    {
        return $project->status === ProjectStatus::WorkingCopy || $project->need_correction;
    }

    public function isPubliclyVisible(Project $project): bool
    {
        if ($project->is_hidden) {
            return false;
        }

        return in_array($project->status, [
            ProjectStatus::Picked,
            ProjectStatus::PickedForRealization,
            ProjectStatus::TeamAccepted,
            ProjectStatus::MeritVerificationAccepted,
        ], true);
    }
}

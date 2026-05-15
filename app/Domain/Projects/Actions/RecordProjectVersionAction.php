<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectVersion;
use App\Models\User;

class RecordProjectVersionAction
{
    public function execute(Project $project, ?User $actor = null): ProjectVersion
    {
        $freshProject = $project->fresh();

        return $freshProject->versions()->create([
            'user_id' => $actor?->id,
            'status' => $freshProject->status,
            'data' => $freshProject->toArray(),
            'files' => $freshProject->files()->get()->toArray(),
            'costs' => $freshProject->costItems()->get()->toArray(),
        ]);
    }
}

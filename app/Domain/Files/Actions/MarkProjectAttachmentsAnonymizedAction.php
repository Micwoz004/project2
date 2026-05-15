<?php

namespace App\Domain\Files\Actions;

use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MarkProjectAttachmentsAnonymizedAction
{
    public function execute(Project $project, ?User $actor = null): Project
    {
        Log::info('project.attachments_anonymize.start', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
        ]);

        $project->forceFill([
            'attachments_anonymized' => true,
        ])->save();

        Log::info('project.attachments_anonymize.success', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
        ]);

        return $project;
    }
}

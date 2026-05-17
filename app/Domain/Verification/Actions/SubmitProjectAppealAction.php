<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Models\ProjectAppeal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitProjectAppealAction
{
    public function execute(Project $project, User $actor, string $appealMessage, bool $adminOverride = false): ProjectAppeal
    {
        Log::info('project.appeal.submit.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'admin_override' => $adminOverride,
        ]);

        $message = trim($appealMessage);
        $this->assertCanSubmit($project, $actor, $message, $adminOverride);

        return DB::transaction(function () use ($project, $actor, $message): ProjectAppeal {
            $appeal = ProjectAppeal::query()->create([
                'project_id' => $project->id,
                'appeal_message' => $message,
            ]);

            Log::info('project.appeal.submit.success', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'appeal_id' => $appeal->id,
            ]);

            return $appeal;
        });
    }

    private function assertCanSubmit(Project $project, User $actor, string $appealMessage, bool $adminOverride): void
    {
        if (! $adminOverride && $project->creator_id !== $actor->id) {
            Log::warning('project.appeal.submit.rejected_actor', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Odwołanie może złożyć tylko autor projektu.');
        }

        if (! $project->status->isRejected()) {
            Log::warning('project.appeal.submit.rejected_status', [
                'project_id' => $project->id,
                'status' => $project->status->value,
            ]);

            throw new DomainException('Aby odwołać się od decyzji, projekt musi najpierw zostać odrzucony.');
        }

        if ($appealMessage === '') {
            Log::warning('project.appeal.submit.rejected_empty_message', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Treść odwołania jest wymagana.');
        }

        if (mb_strlen($appealMessage) > 5000) {
            Log::warning('project.appeal.submit.rejected_message_too_long', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Treść odwołania może mieć maksymalnie 5000 znaków.');
        }

        if ($project->appeal()->exists()) {
            Log::warning('project.appeal.submit.rejected_duplicate', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Dla projektu istnieje już odwołanie.');
        }
    }
}

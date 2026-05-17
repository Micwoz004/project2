<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Verification\Models\ProjectAppeal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RespondProjectAppealAction
{
    public function execute(ProjectAppeal $appeal, User $actor, string $response): ProjectAppeal
    {
        Log::info('project.appeal.response.start', [
            'appeal_id' => $appeal->id,
            'project_id' => $appeal->project_id,
            'actor_id' => $actor->id,
        ]);

        $responseText = trim($response);

        if ($responseText === '') {
            Log::warning('project.appeal.response.rejected_empty', [
                'appeal_id' => $appeal->id,
                'project_id' => $appeal->project_id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Odpowiedź komisji na odwołanie jest wymagana.');
        }

        return DB::transaction(function () use ($appeal, $actor, $responseText): ProjectAppeal {
            $appeal->forceFill([
                'response_to_appeal' => $responseText,
                'response_created_at' => $appeal->response_created_at ?? now(),
            ])->save();

            Log::info('project.appeal.response.success', [
                'appeal_id' => $appeal->id,
                'project_id' => $appeal->project_id,
                'actor_id' => $actor->id,
            ]);

            return $appeal->refresh();
        });
    }
}

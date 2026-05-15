<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Models\BoardVoteRejection;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class RecordBoardVoteRejectionAction
{
    public function execute(Project $project, User $actor, BoardType $boardType, string $comment): BoardVoteRejection
    {
        Log::info('verification.board.rejection.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'board_type' => $boardType->value,
        ]);

        if (! in_array($boardType, [BoardType::At, BoardType::Ot], true)) {
            Log::warning('verification.board.rejection.rejected_type', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Uzasadnienia odrzucenia dotyczą tylko głosowań AT/OT.');
        }

        if (trim($comment) === '') {
            Log::warning('verification.board.rejection.rejected_empty_comment', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Uzasadnienie odrzucenia jest wymagane.');
        }

        $rejection = $project->boardVoteRejections()->create([
            'board_type' => $boardType,
            'comment' => $comment,
            'created_by_id' => $actor->id,
        ]);

        Log::info('verification.board.rejection.success', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'board_rejection_id' => $rejection->id,
            'board_type' => $boardType->value,
        ]);

        return $rejection;
    }
}

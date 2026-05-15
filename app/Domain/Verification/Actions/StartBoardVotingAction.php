<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\BoardType;
use DomainException;
use Illuminate\Support\Facades\Log;

class StartBoardVotingAction
{
    public function execute(Project $project, BoardType $boardType): Project
    {
        Log::info('verification.board.start', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
            'status' => $project->status->value,
        ]);

        $status = match ($boardType) {
            BoardType::Zk, BoardType::Ot => ProjectStatus::DuringTeamVerification,
            BoardType::At => ProjectStatus::DuringTeamRecallVerification,
        };

        if ($project->status === $status) {
            Log::warning('verification.board.start.rejected_already_started', [
                'project_id' => $project->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Głosowanie tego typu jest już rozpoczęte.');
        }

        if ($project->status->isRejected()) {
            $project->was_rejected = true;
        }

        $project->forceFill([
            'status' => $status,
        ])->save();

        Log::info('verification.board.started', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
            'status' => $status->value,
        ]);

        return $project->refresh();
    }
}

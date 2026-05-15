<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\BoardType;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestartBoardVotingAction
{
    public function execute(Project $project, BoardType $boardType): Project
    {
        Log::info('verification.board.restart.start', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
            'status' => $project->status->value,
        ]);

        $status = match ($boardType) {
            BoardType::Ot => ProjectStatus::DuringTeamVerification,
            BoardType::At => ProjectStatus::DuringTeamRecallVerification,
            BoardType::Zk => null,
        };

        if ($status === null) {
            Log::warning('verification.board.restart.rejected_type', [
                'project_id' => $project->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Restart głosowania dotyczy tylko OT albo AT.');
        }

        return DB::transaction(function () use ($project, $boardType, $status): Project {
            $deletedVotes = $project->boardVotes()
                ->where('board_type', $boardType->value)
                ->delete();

            $project->forceFill([
                'status' => $status,
            ])->save();

            Log::info('verification.board.restart.success', [
                'project_id' => $project->id,
                'board_type' => $boardType->value,
                'status' => $status->value,
                'deleted_votes' => $deletedVotes,
            ]);

            return $project->refresh();
        });
    }
}

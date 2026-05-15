<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\BoardType;
use DomainException;
use Illuminate\Support\Facades\Log;

class CloseBoardVotingAction
{
    public function execute(Project $project, BoardType $boardType): Project
    {
        Log::info('verification.board.close.start', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
            'status' => $project->status->value,
        ]);

        $status = match ($boardType) {
            BoardType::Ot => ProjectStatus::TeamClosedVerification,
            BoardType::At => ProjectStatus::TeamRecallClosedVerification,
            BoardType::Zk => null,
        };

        if ($status === null) {
            Log::warning('verification.board.close.rejected_type', [
                'project_id' => $project->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Zamknięcie głosowania bez rozstrzygnięcia dotyczy tylko OT albo AT.');
        }

        if ($project->status === $status) {
            Log::warning('verification.board.close.rejected_already_closed', [
                'project_id' => $project->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Głosowanie tego typu jest już zamknięte.');
        }

        $project->forceFill([
            'status' => $status,
        ])->save();

        Log::info('verification.board.close.success', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
            'status' => $status->value,
        ]);

        return $project->refresh();
    }
}

<?php

namespace App\Domain\Verification\Services;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\AtVoteChoice;
use App\Domain\Verification\Enums\BoardDecision;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\OtVoteChoice;
use App\Domain\Verification\Enums\ZkVoteChoice;
use DomainException;
use Illuminate\Support\Facades\Log;

class BoardDecisionResolver
{
    public function resolve(Project $project, BoardType $boardType): BoardDecision
    {
        return match ($boardType) {
            BoardType::Zk => $this->resolveZk($project),
            BoardType::Ot => $this->resolveOt($project),
            BoardType::At => $this->resolveAt($project),
        };
    }

    public function apply(Project $project, BoardType $boardType): BoardDecision
    {
        Log::info('verification.board.resolve.start', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
        ]);

        $decision = $this->resolve($project, $boardType);

        match ($decision) {
            BoardDecision::Accepted => $project->forceFill([
                'status' => ProjectStatus::Picked,
                'was_rejected' => false,
                'reverify' => false,
            ])->save(),
            BoardDecision::Rejected => $project->forceFill([
                'status' => $boardType === BoardType::At ? ProjectStatus::TeamRejectedFinally : ProjectStatus::RejectedZo,
                'was_rejected' => true,
            ])->save(),
            BoardDecision::RejectedWithRecall => $project->forceFill([
                'status' => ProjectStatus::TeamRejected,
                'was_rejected' => true,
            ])->save(),
            BoardDecision::VerifyAgain => $project->forceFill([
                'status' => ProjectStatus::TeamForReverification,
                'reverify' => true,
            ])->save(),
            BoardDecision::ClosedWithoutDecision => $this->rejectUnresolvedDecision($project, $boardType),
        };

        Log::info('verification.board.resolve.success', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
            'decision' => $decision->value,
            'status' => $project->status->value,
        ]);

        return $decision;
    }

    private function resolveZk(Project $project): BoardDecision
    {
        $accepted = $this->countChoice($project, BoardType::Zk, ZkVoteChoice::Up->value);
        $rejected = $this->countChoice($project, BoardType::Zk, ZkVoteChoice::Down->value);

        if ($accepted === $rejected) {
            return BoardDecision::ClosedWithoutDecision;
        }

        return $accepted > $rejected ? BoardDecision::Accepted : BoardDecision::Rejected;
    }

    private function resolveOt(Project $project): BoardDecision
    {
        $accepted = $this->countChoice($project, BoardType::Ot, OtVoteChoice::Accepted->value);
        $rejected = $this->countChoice($project, BoardType::Ot, OtVoteChoice::RejectedWithRecall->value);
        $reverify = $this->countChoice($project, BoardType::Ot, OtVoteChoice::VerifyAgain->value);
        $withheld = $this->countChoice($project, BoardType::Ot, OtVoteChoice::Withhold->value);

        if ($accepted > $rejected && $accepted > $reverify && $accepted > $withheld) {
            return BoardDecision::Accepted;
        }

        if ($rejected === $reverify) {
            return BoardDecision::ClosedWithoutDecision;
        }

        return $rejected > $reverify ? BoardDecision::RejectedWithRecall : BoardDecision::VerifyAgain;
    }

    private function resolveAt(Project $project): BoardDecision
    {
        $rejected = $this->countChoice($project, BoardType::At, AtVoteChoice::Rejected->value);
        $accepted = $this->countChoice($project, BoardType::At, AtVoteChoice::AcceptedToVote->value);

        if ($rejected === $accepted) {
            return BoardDecision::ClosedWithoutDecision;
        }

        return $accepted > $rejected ? BoardDecision::Accepted : BoardDecision::Rejected;
    }

    private function countChoice(Project $project, BoardType $boardType, int $choice): int
    {
        return $project->boardVotes()
            ->where('board_type', $boardType->value)
            ->where('choice', $choice)
            ->count();
    }

    private function rejectUnresolvedDecision(Project $project, BoardType $boardType): never
    {
        Log::warning('verification.board.resolve.unresolved', [
            'project_id' => $project->id,
            'board_type' => $boardType->value,
        ]);

        throw new DomainException('Głosowanie nie daje jednoznacznego rozstrzygnięcia.');
    }
}

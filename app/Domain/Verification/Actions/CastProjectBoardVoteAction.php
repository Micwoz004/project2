<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\AtVoteChoice;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\OtVoteChoice;
use App\Domain\Verification\Enums\ZkVoteChoice;
use App\Domain\Verification\Models\ProjectBoardVote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class CastProjectBoardVoteAction
{
    public function execute(Project $project, User $actor, BoardType $boardType, int $choice, ?string $comment = null): ProjectBoardVote
    {
        Log::info('verification.board.vote.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'board_type' => $boardType->value,
            'choice' => $choice,
        ]);

        $this->assertChoiceAllowed($boardType, $choice);

        if ($project->boardVotes()->where('user_id', $actor->id)->where('board_type', $boardType->value)->exists()) {
            Log::warning('verification.board.vote.rejected_duplicate', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'board_type' => $boardType->value,
            ]);

            throw new DomainException('Użytkownik oddał już głos dla tego projektu i typu głosowania.');
        }

        $vote = $project->boardVotes()->create([
            'user_id' => $actor->id,
            'board_type' => $boardType,
            'choice' => $choice,
            'comment' => $comment,
        ]);

        Log::info('verification.board.vote.success', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'board_vote_id' => $vote->id,
            'board_type' => $boardType->value,
        ]);

        return $vote;
    }

    private function assertChoiceAllowed(BoardType $boardType, int $choice): void
    {
        $allowed = match ($boardType) {
            BoardType::Zk => array_map(static fn (ZkVoteChoice $voteChoice): int => $voteChoice->value, ZkVoteChoice::cases()),
            BoardType::Ot => array_map(static fn (OtVoteChoice $voteChoice): int => $voteChoice->value, OtVoteChoice::cases()),
            BoardType::At => array_map(static fn (AtVoteChoice $voteChoice): int => $voteChoice->value, AtVoteChoice::cases()),
        };

        if (! in_array($choice, $allowed, true)) {
            Log::warning('verification.board.vote.rejected_invalid_choice', [
                'board_type' => $boardType->value,
                'choice' => $choice,
            ]);

            throw new DomainException('Nieprawidłowy wybór dla typu głosowania.');
        }
    }
}

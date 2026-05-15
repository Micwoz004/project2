<?php

namespace App\Domain\Voting\Actions;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Services\CastVoteService;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterPaperVoteCardAction
{
    public function __construct(
        private readonly CastVoteService $castVoteService,
    ) {}

    public function execute(
        BudgetEdition $edition,
        VoterIdentityData $identity,
        array $localProjectIds,
        array $citywideProjectIds,
        User $operator,
        array $context = [],
    ): VoteCard {
        Log::info('paper_vote_card.register.start', [
            'budget_edition_id' => $edition->id,
            'operator_id' => $operator->id,
            'local_count' => count($localProjectIds),
            'citywide_count' => count($citywideProjectIds),
        ]);

        if (! $this->canRegisterPaperCards($operator)) {
            Log::warning('paper_vote_card.register.rejected_permission', [
                'budget_edition_id' => $edition->id,
                'operator_id' => $operator->id,
            ]);

            throw new DomainException('Brak uprawnień do rejestracji papierowej karty głosowania.');
        }

        return DB::transaction(function () use (
            $edition,
            $identity,
            $localProjectIds,
            $citywideProjectIds,
            $operator,
            $context,
        ): VoteCard {
            $edition->refresh();
            $cardNo = $edition->current_paper_card_no + 1;

            $voteCard = $this->castVoteService->cast(
                $edition,
                $identity,
                $localProjectIds,
                $citywideProjectIds,
                $context,
            );

            $voteCard->forceFill([
                'card_no' => $cardNo,
                'digital' => false,
                'created_by_id' => $operator->id,
            ])->save();

            $edition->forceFill([
                'current_paper_card_no' => $cardNo,
            ])->save();

            Log::info('paper_vote_card.register.success', [
                'budget_edition_id' => $edition->id,
                'operator_id' => $operator->id,
                'vote_card_id' => $voteCard->id,
                'card_no' => $cardNo,
            ]);

            return $voteCard->refresh();
        });
    }

    private function canRegisterPaperCards(User $operator): bool
    {
        return $operator->can('vote_cards.manage')
            || $operator->can('voting.manage')
            || $operator->hasAnyRole(['admin', 'bdo']);
    }
}

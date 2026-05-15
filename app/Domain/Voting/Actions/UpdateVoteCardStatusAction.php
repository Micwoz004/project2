<?php

namespace App\Domain\Voting\Actions;

use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpdateVoteCardStatusAction
{
    public function execute(
        VoteCard $voteCard,
        VoteCardStatus $status,
        User $operator,
        ?string $notes = null,
    ): VoteCard {
        Log::info('vote_card.status_update.start', [
            'vote_card_id' => $voteCard->id,
            'operator_id' => $operator->id,
            'from_status' => $voteCard->status->value,
            'to_status' => $status->value,
        ]);

        $voteCard->forceFill([
            'status' => $status,
            'checkout_user_id' => $operator->id,
            'checkout_date_time' => now(),
            'notes' => $notes,
        ])->save();

        Log::info('vote_card.status_update.success', [
            'vote_card_id' => $voteCard->id,
            'operator_id' => $operator->id,
            'status' => $status->value,
        ]);

        return $voteCard->refresh();
    }
}

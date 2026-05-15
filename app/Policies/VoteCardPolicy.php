<?php

namespace App\Policies;

use App\Domain\Voting\Models\VoteCard;
use App\Models\User;

class VoteCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesVoteCards($user);
    }

    public function view(User $user, VoteCard $voteCard): bool
    {
        return $this->managesVoteCards($user);
    }

    public function update(User $user, VoteCard $voteCard): bool
    {
        return $this->managesVoteCards($user);
    }

    public function delete(User $user, VoteCard $voteCard): bool
    {
        return false;
    }

    private function managesVoteCards(User $user): bool
    {
        return $user->can('vote_cards.manage') || $user->hasAnyRole(['admin', 'bdo']);
    }
}

<?php

namespace App\Products\CivicBudget\Domain\Verification\Enums;

enum AtVoteChoice: int
{
    case Withhold = 1;
    case AcceptedToVote = 2;
    case Rejected = 3;
}

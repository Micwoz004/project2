<?php

namespace App\Domain\Verification\Enums;

enum AtVoteChoice: int
{
    case Withhold = 1;
    case AcceptedToVote = 2;
    case Rejected = 3;
}

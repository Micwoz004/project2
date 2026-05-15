<?php

namespace App\Domain\Verification\Enums;

enum OtVoteChoice: int
{
    case Withhold = 1;
    case VerifyAgain = 2;
    case RejectedWithRecall = 3;
    case Accepted = 4;
}

<?php

namespace App\Products\CivicBudget\Domain\Voting\Enums;

enum VotingTokenType: int
{
    case Email = 1;
    case Sms = 2;
}

<?php

namespace App\Products\CivicBudget\Domain\Verification\Enums;

enum ZkVoteChoice: int
{
    case Down = -1;
    case Up = 1;
}

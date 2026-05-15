<?php

namespace App\Domain\Verification\Enums;

enum ZkVoteChoice: int
{
    case Down = -1;
    case Up = 1;
}

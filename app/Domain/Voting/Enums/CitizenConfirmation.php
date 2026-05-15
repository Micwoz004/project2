<?php

namespace App\Domain\Voting\Enums;

enum CitizenConfirmation: int
{
    case Default = 1;
    case Living = 2;
    case Commuting = 3;
}

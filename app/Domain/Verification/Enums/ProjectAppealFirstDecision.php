<?php

namespace App\Domain\Verification\Enums;

enum ProjectAppealFirstDecision: int
{
    case Pending = 0;
    case Rejected = 1;
    case Accepted = 2;
}

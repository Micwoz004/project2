<?php

namespace App\Products\CivicBudget\Domain\Verification\Enums;

enum VerificationCardStatus: int
{
    case WorkingCopy = 1;
    case Sent = 2;
}

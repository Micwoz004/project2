<?php

namespace App\Domain\Verification\Enums;

enum BoardDecision: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case RejectedWithRecall = 'rejected_with_recall';
    case VerifyAgain = 'verify_again';
    case ClosedWithoutDecision = 'closed_without_decision';
}

<?php

namespace App\Domain\Verification\Enums;

enum VerificationAssignmentType: int
{
    case MeritInitial = 1;
    case MeritFinish = 2;
    case Consultation = 3;
    case FormalVerification = 4;
}

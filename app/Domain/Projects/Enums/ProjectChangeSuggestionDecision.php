<?php

namespace App\Domain\Projects\Enums;

enum ProjectChangeSuggestionDecision: int
{
    case Pending = 0;
    case Declined = 1;
    case Accepted = 2;
}

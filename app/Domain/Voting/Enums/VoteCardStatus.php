<?php

namespace App\Domain\Voting\Enums;

enum VoteCardStatus: int
{
    case Accepted = 1;
    case Rejected = 2;
    case Verifying = 3;

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'ważna',
            self::Rejected => 'nieważna',
            self::Verifying => 'rozpatrywana',
        };
    }
}

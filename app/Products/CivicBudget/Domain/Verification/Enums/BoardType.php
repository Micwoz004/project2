<?php

namespace App\Products\CivicBudget\Domain\Verification\Enums;

enum BoardType: string
{
    case Zk = 'ZK';
    case Ot = 'OT';
    case At = 'AT';
}

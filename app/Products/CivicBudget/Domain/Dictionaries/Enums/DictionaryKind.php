<?php

namespace App\Products\CivicBudget\Domain\Dictionaries\Enums;

enum DictionaryKind: string
{
    case FirstName = 'first_name';
    case LastName = 'last_name';
    case MotherLastName = 'mother_last_name';
}

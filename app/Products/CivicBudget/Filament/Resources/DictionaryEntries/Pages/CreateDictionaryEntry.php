<?php

namespace App\Products\CivicBudget\Filament\Resources\DictionaryEntries\Pages;

use App\Products\CivicBudget\Filament\Resources\DictionaryEntries\DictionaryEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDictionaryEntry extends CreateRecord
{
    protected static string $resource = DictionaryEntryResource::class;
}

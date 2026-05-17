<?php

namespace App\Filament\Resources\DictionaryEntries\Pages;

use App\Filament\Resources\DictionaryEntries\DictionaryEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDictionaryEntry extends CreateRecord
{
    protected static string $resource = DictionaryEntryResource::class;
}

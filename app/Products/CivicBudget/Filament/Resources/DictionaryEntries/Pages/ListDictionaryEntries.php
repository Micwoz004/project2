<?php

namespace App\Products\CivicBudget\Filament\Resources\DictionaryEntries\Pages;

use App\Products\CivicBudget\Filament\Resources\DictionaryEntries\DictionaryEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDictionaryEntries extends ListRecords
{
    protected static string $resource = DictionaryEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

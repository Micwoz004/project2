<?php

namespace App\Filament\Resources\DictionaryEntries\Pages;

use App\Filament\Resources\DictionaryEntries\DictionaryEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDictionaryEntry extends EditRecord
{
    protected static string $resource = DictionaryEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

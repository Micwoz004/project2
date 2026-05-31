<?php

namespace App\Products\EcoServices\Filament\Resources\WasteItems\Pages;

use App\Products\EcoServices\Filament\Resources\WasteItems\WasteItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWasteItems extends ListRecords
{
    protected static string $resource = WasteItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

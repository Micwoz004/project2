<?php

namespace App\Products\EcoUslugi\Filament\Resources\WasteItems\Pages;

use App\Products\EcoUslugi\Filament\Resources\WasteItems\WasteItemResource;
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

<?php

namespace App\Products\EcoUslugi\Filament\Resources\EcoZones\Pages;

use App\Products\EcoUslugi\Filament\Resources\EcoZones\EcoZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEcoZones extends ListRecords
{
    protected static string $resource = EcoZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

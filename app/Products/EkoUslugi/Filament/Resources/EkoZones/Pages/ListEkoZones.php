<?php

namespace App\Products\EkoUslugi\Filament\Resources\EkoZones\Pages;

use App\Products\EkoUslugi\Filament\Resources\EkoZones\EkoZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEkoZones extends ListRecords
{
    protected static string $resource = EkoZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Products\EcoServices\Filament\Resources\EcoZones\Pages;

use App\Products\EcoServices\Filament\Resources\EcoZones\EcoZoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEcoZone extends EditRecord
{
    protected static string $resource = EcoZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

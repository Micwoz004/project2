<?php

namespace App\Products\EkoUslugi\Filament\Resources\EkoZones\Pages;

use App\Products\EkoUslugi\Filament\Resources\EkoZones\EkoZoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEkoZone extends EditRecord
{
    protected static string $resource = EkoZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

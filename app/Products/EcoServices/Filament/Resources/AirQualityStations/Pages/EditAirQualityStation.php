<?php

namespace App\Products\EcoServices\Filament\Resources\AirQualityStations\Pages;

use App\Products\EcoServices\Filament\Resources\AirQualityStations\AirQualityStationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAirQualityStation extends EditRecord
{
    protected static string $resource = AirQualityStationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

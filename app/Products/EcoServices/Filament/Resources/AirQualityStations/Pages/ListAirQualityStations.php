<?php

namespace App\Products\EcoServices\Filament\Resources\AirQualityStations\Pages;

use App\Products\EcoServices\Domain\AirQuality\Actions\SyncAirQualityStationsAction;
use App\Products\EcoServices\Filament\Resources\AirQualityStations\AirQualityStationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAirQualityStations extends ListRecords
{
    protected static string $resource = AirQualityStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncGios')
                ->label('Synchronizuj GIOŚ')
                ->action(function (SyncAirQualityStationsAction $sync): void {
                    $stats = $sync->execute();

                    Notification::make()
                        ->title('Synchronizacja GIOŚ zakończona')
                        ->body("Zsynchronizowano {$stats['stations']} stacji i {$stats['readings']} odczytów.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}

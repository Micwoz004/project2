<?php

namespace App\Products\CivicBudget\Filament\Resources\ApplicationSettings\Pages;

use App\Products\CivicBudget\Filament\Resources\ApplicationSettings\ApplicationSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApplicationSettings extends ListRecords
{
    protected static string $resource = ApplicationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

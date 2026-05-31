<?php

namespace App\Products\CivicBudget\Filament\Resources\ApplicationSettings\Pages;

use App\Products\CivicBudget\Filament\Resources\ApplicationSettings\ApplicationSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplicationSetting extends CreateRecord
{
    protected static string $resource = ApplicationSettingResource::class;
}

<?php

namespace App\Filament\Resources\ApplicationSettings\Pages;

use App\Filament\Resources\ApplicationSettings\ApplicationSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplicationSetting extends CreateRecord
{
    protected static string $resource = ApplicationSettingResource::class;
}

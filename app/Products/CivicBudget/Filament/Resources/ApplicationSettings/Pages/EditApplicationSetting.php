<?php

namespace App\Products\CivicBudget\Filament\Resources\ApplicationSettings\Pages;

use App\Products\CivicBudget\Filament\Resources\ApplicationSettings\ApplicationSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApplicationSetting extends EditRecord
{
    protected static string $resource = ApplicationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

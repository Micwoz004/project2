<?php

namespace App\Products\CivicBudget\Filament\Resources\ProjectAreas\Pages;

use App\Products\CivicBudget\Filament\Resources\ProjectAreas\ProjectAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectArea extends EditRecord
{
    protected static string $resource = ProjectAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

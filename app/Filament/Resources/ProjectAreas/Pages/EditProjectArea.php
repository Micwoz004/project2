<?php

namespace App\Filament\Resources\ProjectAreas\Pages;

use App\Filament\Resources\ProjectAreas\ProjectAreaResource;
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

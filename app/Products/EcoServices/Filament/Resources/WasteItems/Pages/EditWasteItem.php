<?php

namespace App\Products\EcoServices\Filament\Resources\WasteItems\Pages;

use App\Products\EcoServices\Filament\Resources\WasteItems\WasteItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteItem extends EditRecord
{
    protected static string $resource = WasteItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

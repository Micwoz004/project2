<?php

namespace App\Products\EcoUslugi\Filament\Resources\WasteItems\Pages;

use App\Products\EcoUslugi\Filament\Resources\WasteItems\WasteItemResource;
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

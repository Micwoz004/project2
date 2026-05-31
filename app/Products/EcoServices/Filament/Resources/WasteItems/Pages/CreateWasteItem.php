<?php

namespace App\Products\EcoServices\Filament\Resources\WasteItems\Pages;

use App\Products\EcoServices\Filament\Resources\WasteItems\WasteItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWasteItem extends CreateRecord
{
    protected static string $resource = WasteItemResource::class;
}

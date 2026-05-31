<?php

namespace App\Products\EcoServices\Filament\Resources\WasteFractions\Pages;

use App\Products\EcoServices\Filament\Resources\WasteFractions\WasteFractionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteFraction extends EditRecord
{
    protected static string $resource = WasteFractionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

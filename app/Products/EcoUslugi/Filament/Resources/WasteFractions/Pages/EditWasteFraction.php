<?php

namespace App\Products\EcoUslugi\Filament\Resources\WasteFractions\Pages;

use App\Products\EcoUslugi\Filament\Resources\WasteFractions\WasteFractionResource;
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

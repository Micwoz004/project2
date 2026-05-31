<?php

namespace App\Products\EcoServices\Filament\Resources\WasteFractions\Pages;

use App\Products\EcoServices\Filament\Resources\WasteFractions\WasteFractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWasteFractions extends ListRecords
{
    protected static string $resource = WasteFractionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

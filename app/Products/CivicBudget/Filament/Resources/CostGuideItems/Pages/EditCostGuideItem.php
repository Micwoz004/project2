<?php

namespace App\Products\CivicBudget\Filament\Resources\CostGuideItems\Pages;

use App\Products\CivicBudget\Filament\Resources\CostGuideItems\CostGuideItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCostGuideItem extends EditRecord
{
    protected static string $resource = CostGuideItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

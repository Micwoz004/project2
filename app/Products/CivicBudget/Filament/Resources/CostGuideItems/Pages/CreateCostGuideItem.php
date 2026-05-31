<?php

namespace App\Products\CivicBudget\Filament\Resources\CostGuideItems\Pages;

use App\Products\CivicBudget\Filament\Resources\CostGuideItems\CostGuideItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCostGuideItem extends CreateRecord
{
    protected static string $resource = CostGuideItemResource::class;
}

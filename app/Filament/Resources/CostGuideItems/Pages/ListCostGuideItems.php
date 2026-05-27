<?php

namespace App\Filament\Resources\CostGuideItems\Pages;

use App\Filament\Resources\CostGuideItems\CostGuideItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCostGuideItems extends ListRecords
{
    protected static string $resource = CostGuideItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

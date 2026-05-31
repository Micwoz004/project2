<?php

namespace App\Products\EcoServices\Filament\Resources\NewsCategories\Pages;

use App\Products\EcoServices\Filament\Resources\NewsCategories\NewsCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNewsCategories extends ListRecords
{
    protected static string $resource = NewsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

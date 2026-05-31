<?php

namespace App\Products\EcoServices\Filament\Resources\NewsCategories\Pages;

use App\Products\EcoServices\Filament\Resources\NewsCategories\NewsCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsCategory extends CreateRecord
{
    protected static string $resource = NewsCategoryResource::class;
}

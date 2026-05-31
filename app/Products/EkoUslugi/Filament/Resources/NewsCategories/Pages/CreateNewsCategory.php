<?php

namespace App\Products\EkoUslugi\Filament\Resources\NewsCategories\Pages;

use App\Products\EkoUslugi\Filament\Resources\NewsCategories\NewsCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsCategory extends CreateRecord
{
    protected static string $resource = NewsCategoryResource::class;
}

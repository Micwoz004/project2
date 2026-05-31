<?php

namespace App\Products\CivicBudget\Filament\Resources\Categories\Pages;

use App\Products\CivicBudget\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}

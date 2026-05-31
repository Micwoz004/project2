<?php

namespace App\Products\EcoUslugi\Filament\Resources\NewsPosts\Pages;

use App\Products\EcoUslugi\Filament\Resources\NewsPosts\NewsPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;
}

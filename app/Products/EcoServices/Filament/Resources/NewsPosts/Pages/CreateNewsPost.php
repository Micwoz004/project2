<?php

namespace App\Products\EcoServices\Filament\Resources\NewsPosts\Pages;

use App\Products\EcoServices\Filament\Resources\NewsPosts\NewsPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;
}

<?php

namespace App\Platform\Filament\Resources\ClientProducts\Pages;

use App\Platform\Filament\Resources\ClientProducts\ClientProductResource;
use Filament\Resources\Pages\ListRecords;

class ListClientProducts extends ListRecords
{
    protected static string $resource = ClientProductResource::class;
}

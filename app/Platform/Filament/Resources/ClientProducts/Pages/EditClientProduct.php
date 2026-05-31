<?php

namespace App\Platform\Filament\Resources\ClientProducts\Pages;

use App\Platform\Filament\Resources\ClientProducts\ClientProductResource;
use Filament\Resources\Pages\EditRecord;

class EditClientProduct extends EditRecord
{
    protected static string $resource = ClientProductResource::class;
}

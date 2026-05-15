<?php

namespace App\Filament\Resources\ProjectAreas\Pages;

use App\Filament\Resources\ProjectAreas\ProjectAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectAreas extends ListRecords
{
    protected static string $resource = ProjectAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

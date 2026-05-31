<?php

namespace App\Products\EcoUslugi\Filament\Resources\PszokPoints\Pages;

use App\Products\EcoUslugi\Filament\Resources\PszokPoints\PszokPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPszokPoints extends ListRecords
{
    protected static string $resource = PszokPointResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

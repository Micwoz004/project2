<?php

namespace App\Products\EcoServices\Filament\Resources\PszokPoints\Pages;

use App\Products\EcoServices\Filament\Resources\PszokPoints\PszokPointResource;
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

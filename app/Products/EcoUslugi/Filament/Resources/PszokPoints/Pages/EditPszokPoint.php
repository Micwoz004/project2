<?php

namespace App\Products\EcoUslugi\Filament\Resources\PszokPoints\Pages;

use App\Products\EcoUslugi\Filament\Resources\PszokPoints\PszokPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPszokPoint extends EditRecord
{
    protected static string $resource = PszokPointResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

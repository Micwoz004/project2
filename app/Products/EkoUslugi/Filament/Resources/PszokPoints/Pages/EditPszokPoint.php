<?php

namespace App\Products\EkoUslugi\Filament\Resources\PszokPoints\Pages;

use App\Products\EkoUslugi\Filament\Resources\PszokPoints\PszokPointResource;
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

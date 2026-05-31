<?php

namespace App\Products\CivicBudget\Filament\Resources\ContentPages\Pages;

use App\Products\CivicBudget\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentPage extends EditRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

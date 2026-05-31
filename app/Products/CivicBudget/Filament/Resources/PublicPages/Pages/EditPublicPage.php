<?php

namespace App\Products\CivicBudget\Filament\Resources\PublicPages\Pages;

use App\Products\CivicBudget\Filament\Resources\PublicPages\PublicPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicPage extends EditRecord
{
    protected static string $resource = PublicPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

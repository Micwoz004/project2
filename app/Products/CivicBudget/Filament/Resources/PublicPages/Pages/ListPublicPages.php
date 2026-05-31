<?php

namespace App\Products\CivicBudget\Filament\Resources\PublicPages\Pages;

use App\Products\CivicBudget\Filament\Resources\PublicPages\PublicPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicPages extends ListRecords
{
    protected static string $resource = PublicPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

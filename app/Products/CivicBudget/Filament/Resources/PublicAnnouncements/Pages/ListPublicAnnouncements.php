<?php

namespace App\Products\CivicBudget\Filament\Resources\PublicAnnouncements\Pages;

use App\Products\CivicBudget\Filament\Resources\PublicAnnouncements\PublicAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicAnnouncements extends ListRecords
{
    protected static string $resource = PublicAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

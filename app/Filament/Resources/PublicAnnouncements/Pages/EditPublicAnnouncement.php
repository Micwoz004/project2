<?php

namespace App\Filament\Resources\PublicAnnouncements\Pages;

use App\Filament\Resources\PublicAnnouncements\PublicAnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicAnnouncement extends EditRecord
{
    protected static string $resource = PublicAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

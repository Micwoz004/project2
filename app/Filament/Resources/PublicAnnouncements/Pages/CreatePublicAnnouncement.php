<?php

namespace App\Filament\Resources\PublicAnnouncements\Pages;

use App\Filament\Resources\PublicAnnouncements\PublicAnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePublicAnnouncement extends CreateRecord
{
    protected static string $resource = PublicAnnouncementResource::class;
}

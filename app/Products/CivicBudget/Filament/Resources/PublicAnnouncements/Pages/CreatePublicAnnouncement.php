<?php

namespace App\Products\CivicBudget\Filament\Resources\PublicAnnouncements\Pages;

use App\Products\CivicBudget\Filament\Resources\PublicAnnouncements\PublicAnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePublicAnnouncement extends CreateRecord
{
    protected static string $resource = PublicAnnouncementResource::class;
}

<?php

namespace App\Products\EkoUslugi\Filament\Resources\CollectionSchedules\Pages;

use App\Products\EkoUslugi\Filament\Resources\CollectionSchedules\CollectionScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollectionSchedule extends EditRecord
{
    protected static string $resource = CollectionScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

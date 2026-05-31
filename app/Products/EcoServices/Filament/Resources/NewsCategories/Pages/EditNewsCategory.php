<?php

namespace App\Products\EcoServices\Filament\Resources\NewsCategories\Pages;

use App\Products\EcoServices\Filament\Resources\NewsCategories\NewsCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNewsCategory extends EditRecord
{
    protected static string $resource = NewsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

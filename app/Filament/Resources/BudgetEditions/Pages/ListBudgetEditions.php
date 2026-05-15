<?php

namespace App\Filament\Resources\BudgetEditions\Pages;

use App\Filament\Resources\BudgetEditions\BudgetEditionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetEditions extends ListRecords
{
    protected static string $resource = BudgetEditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Products\CivicBudget\Filament\Resources\ProjectChangeSuggestions\Pages;

use App\Products\CivicBudget\Filament\Resources\ProjectChangeSuggestions\ProjectChangeSuggestionResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectChangeSuggestions extends ListRecords
{
    protected static string $resource = ProjectChangeSuggestionResource::class;
}

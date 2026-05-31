<?php

namespace App\Products\CivicBudget\Filament\Resources\ProjectPublicComments\Pages;

use App\Products\CivicBudget\Filament\Resources\ProjectPublicComments\ProjectPublicCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectPublicComments extends ListRecords
{
    protected static string $resource = ProjectPublicCommentResource::class;
}

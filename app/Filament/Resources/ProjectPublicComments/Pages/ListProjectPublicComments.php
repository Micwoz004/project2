<?php

namespace App\Filament\Resources\ProjectPublicComments\Pages;

use App\Filament\Resources\ProjectPublicComments\ProjectPublicCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectPublicComments extends ListRecords
{
    protected static string $resource = ProjectPublicCommentResource::class;
}

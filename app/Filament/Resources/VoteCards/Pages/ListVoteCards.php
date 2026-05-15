<?php

namespace App\Filament\Resources\VoteCards\Pages;

use App\Filament\Resources\VoteCards\VoteCardResource;
use Filament\Resources\Pages\ListRecords;

class ListVoteCards extends ListRecords
{
    protected static string $resource = VoteCardResource::class;
}

<?php

namespace App\Filament\Resources\VoteCards\Pages;

use App\Filament\Resources\VoteCards\VoteCardResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVoteCards extends ListRecords
{
    protected static string $resource = VoteCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registerPaperVoteCard')
                ->label('Dodaj papierową kartę')
                ->schema(VoteCardResource::paperVoteCardFormSchema())
                ->action(fn (array $data) => VoteCardResource::registerPaperVoteCardFromAdminForm($data)),
        ];
    }
}

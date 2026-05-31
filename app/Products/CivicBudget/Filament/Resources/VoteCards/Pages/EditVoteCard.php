<?php

namespace App\Products\CivicBudget\Filament\Resources\VoteCards\Pages;

use App\Products\CivicBudget\Domain\Voting\Actions\UpdateVoteCardStatusAction;
use App\Products\CivicBudget\Domain\Voting\Enums\VoteCardStatus;
use App\Products\CivicBudget\Domain\Voting\Models\VoteCard;
use App\Products\CivicBudget\Filament\Resources\VoteCards\VoteCardResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditVoteCard extends EditRecord
{
    protected static string $resource = VoteCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('replaceVoteCardVotes')
                ->label('Zmień głosy')
                ->schema(VoteCardResource::replaceVoteCardVotesFormSchema())
                ->action(fn (array $data) => VoteCardResource::replaceVoteCardVotesFromAdminForm($this->getRecord(), $data)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var VoteCard $record */
        $operator = Auth::user();

        if ($operator instanceof User) {
            return app(UpdateVoteCardStatusAction::class)->execute(
                $record,
                VoteCardStatus::from((int) $data['status']),
                $operator,
                $data['notes'] ?? null,
            );
        }

        return parent::handleRecordUpdate($record, $data);
    }
}

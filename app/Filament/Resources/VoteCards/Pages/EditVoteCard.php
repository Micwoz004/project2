<?php

namespace App\Filament\Resources\VoteCards\Pages;

use App\Domain\Voting\Actions\UpdateVoteCardStatusAction;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Filament\Resources\VoteCards\VoteCardResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditVoteCard extends EditRecord
{
    protected static string $resource = VoteCardResource::class;

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

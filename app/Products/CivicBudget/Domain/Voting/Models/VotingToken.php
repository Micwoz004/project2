<?php

namespace App\Products\CivicBudget\Domain\Voting\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\Voting\Enums\VotingTokenType;
use Illuminate\Database\Eloquent\Model;

class VotingToken extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
            'type' => VotingTokenType::class,
            'extra_data' => 'array',
        ];
    }
}

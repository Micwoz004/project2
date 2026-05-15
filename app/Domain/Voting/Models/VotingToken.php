<?php

namespace App\Domain\Voting\Models;

use App\Domain\Voting\Enums\VotingTokenType;
use Illuminate\Database\Eloquent\Model;

class VotingToken extends Model
{
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

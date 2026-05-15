<?php

namespace App\Domain\Voting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voter extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function voteCards(): HasMany
    {
        return $this->hasMany(VoteCard::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}

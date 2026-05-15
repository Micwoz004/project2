<?php

namespace App\Domain\Voting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $guarded = [];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }
}

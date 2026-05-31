<?php

namespace App\Products\CivicBudget\Domain\Voting\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }
}

<?php

namespace App\Products\CivicBudget\Domain\Voting\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class VoterRegistryHash extends Model
{
    use BelongsToClient;

    protected $guarded = [];
}

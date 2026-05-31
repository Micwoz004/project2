<?php

namespace App\Products\CivicBudget\Domain\Projects\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusLabel extends Model
{
    use BelongsToClient;

    protected $guarded = [];
}

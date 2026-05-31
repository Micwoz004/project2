<?php

namespace App\Products\CivicBudget\Domain\Settings\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    use BelongsToClient;

    protected $guarded = [];
}

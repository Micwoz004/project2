<?php

namespace App\Platform\Users\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class LegacyPeselRecord extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected $hidden = [
        'pesel',
    ];
}

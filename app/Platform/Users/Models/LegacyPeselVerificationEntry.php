<?php

namespace App\Platform\Users\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class LegacyPeselVerificationEntry extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected $hidden = [
        'pesel',
    ];
}

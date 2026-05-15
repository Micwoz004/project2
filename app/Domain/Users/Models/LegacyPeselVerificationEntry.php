<?php

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyPeselVerificationEntry extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'pesel',
    ];
}

<?php

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyPeselRecord extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'pesel',
    ];
}

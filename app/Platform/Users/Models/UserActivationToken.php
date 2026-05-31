<?php

namespace App\Platform\Users\Models;

use App\Platform\Users\Enums\ActivationTokenType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivationToken extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'hash',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivationTokenType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

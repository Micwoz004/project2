<?php

namespace App\Domain\Users\Models;

use App\Domain\Users\Enums\ActivationTokenType;
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

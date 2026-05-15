<?php

namespace App\Domain\Verification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_data' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Products\EcoServices\Domain\Address\Models;

use App\Models\User;
use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentAddress extends Model
{
    use BelongsToClient;

    protected $table = 'eco_resident_addresses';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'confirmation_decided_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EcoZone::class, 'eco_zone_id');
    }
}

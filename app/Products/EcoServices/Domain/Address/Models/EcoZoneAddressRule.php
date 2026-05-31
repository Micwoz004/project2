<?php

namespace App\Products\EcoServices\Domain\Address\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcoZoneAddressRule extends Model
{
    use BelongsToClient;

    protected $table = 'eco_zone_address_rules';

    protected $guarded = [];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EcoZone::class, 'eco_zone_id');
    }
}

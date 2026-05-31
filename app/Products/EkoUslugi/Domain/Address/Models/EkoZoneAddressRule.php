<?php

namespace App\Products\EkoUslugi\Domain\Address\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkoZoneAddressRule extends Model
{
    use BelongsToClient;

    protected $table = 'eko_zone_address_rules';

    protected $guarded = [];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EkoZone::class, 'eko_zone_id');
    }
}

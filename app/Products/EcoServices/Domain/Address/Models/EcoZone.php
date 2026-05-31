<?php

namespace App\Products\EcoServices\Domain\Address\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcoZone extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eco_zones';

    protected $guarded = [];

    public function rules(): HasMany
    {
        return $this->hasMany(EcoZoneAddressRule::class, 'eco_zone_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

<?php

namespace App\Products\EkoUslugi\Domain\Address\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EkoZone extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eko_zones';

    protected $guarded = [];

    public function rules(): HasMany
    {
        return $this->hasMany(EkoZoneAddressRule::class, 'eko_zone_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

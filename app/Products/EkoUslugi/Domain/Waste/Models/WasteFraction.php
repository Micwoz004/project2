<?php

namespace App\Products\EkoUslugi\Domain\Waste\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EkoUslugi\Domain\Pszok\Models\PszokPoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WasteFraction extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eko_waste_fractions';

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(WasteItem::class, 'eko_waste_fraction_id');
    }

    public function pszokPoints(): BelongsToMany
    {
        return $this->belongsToMany(PszokPoint::class, 'eko_pszok_fraction', 'eko_waste_fraction_id', 'eko_pszok_point_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

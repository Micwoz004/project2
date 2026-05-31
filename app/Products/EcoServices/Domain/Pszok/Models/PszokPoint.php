<?php

namespace App\Products\EcoServices\Domain\Pszok\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EcoServices\Domain\Waste\Models\WasteFraction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PszokPoint extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eco_pszok_points';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function fractions(): BelongsToMany
    {
        return $this->belongsToMany(WasteFraction::class, 'eco_pszok_fraction', 'eco_pszok_point_id', 'eco_waste_fraction_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

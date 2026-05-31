<?php

namespace App\Products\EkoUslugi\Domain\AirQuality\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AirQualityStation extends Model
{
    use BelongsToClient;

    protected $table = 'eko_air_quality_stations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function readings(): HasMany
    {
        return $this->hasMany(AirQualityReading::class, 'eko_air_quality_station_id');
    }

    public function latestReadings(): HasMany
    {
        return $this->readings()->latest('measured_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

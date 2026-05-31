<?php

namespace App\Products\EkoUslugi\Domain\AirQuality\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirQualityReading extends Model
{
    use BelongsToClient;

    protected $table = 'eko_air_quality_readings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'measured_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(AirQualityStation::class, 'eko_air_quality_station_id');
    }
}

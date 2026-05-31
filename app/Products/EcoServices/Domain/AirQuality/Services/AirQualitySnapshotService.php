<?php

namespace App\Products\EcoServices\Domain\AirQuality\Services;

use App\Products\EcoServices\Domain\AirQuality\Models\AirQualityStation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AirQualitySnapshotService
{
    /**
     * @return Collection<int, AirQualityStation>
     */
    public function stations(): Collection
    {
        Log::info('eco_services.air_quality.index.start');

        $stations = AirQualityStation::query()
            ->with(['latestReadings' => fn ($query) => $query->limit(6)])
            ->active()
            ->orderBy('name')
            ->get();

        Log::info('eco_services.air_quality.index.success', [
            'stations_count' => $stations->count(),
        ]);

        return $stations;
    }
}

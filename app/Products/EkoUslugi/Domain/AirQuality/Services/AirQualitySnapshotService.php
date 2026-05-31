<?php

namespace App\Products\EkoUslugi\Domain\AirQuality\Services;

use App\Products\EkoUslugi\Domain\AirQuality\Models\AirQualityStation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AirQualitySnapshotService
{
    /**
     * @return Collection<int, AirQualityStation>
     */
    public function stations(): Collection
    {
        Log::info('eko_uslugi.air_quality.index.start');

        $stations = AirQualityStation::query()
            ->with(['latestReadings' => fn ($query) => $query->limit(6)])
            ->active()
            ->orderBy('name')
            ->get();

        Log::info('eko_uslugi.air_quality.index.success', [
            'stations_count' => $stations->count(),
        ]);

        return $stations;
    }
}

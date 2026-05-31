<?php

namespace App\Products\EcoUslugi\Domain\AirQuality\Actions;

use App\Products\EcoUslugi\Domain\AirQuality\Models\AirQualityReading;
use App\Products\EcoUslugi\Domain\AirQuality\Models\AirQualityStation;
use App\Products\EcoUslugi\Domain\AirQuality\Services\GiosAirQualityClient;
use Illuminate\Support\Facades\Log;

class SyncAirQualityStationsAction
{
    public function __construct(private readonly GiosAirQualityClient $client) {}

    /**
     * @return array{stations:int, readings:int}
     */
    public function execute(): array
    {
        Log::info('eco_uslugi.air_quality.sync.start');

        $stationsCount = 0;
        $readingsCount = 0;

        foreach ($this->client->stations() as $stationPayload) {
            $externalId = (string) ($stationPayload['id'] ?? '');

            if ($externalId === '') {
                continue;
            }

            $station = AirQualityStation::query()->updateOrCreate(
                ['external_id' => $externalId],
                [
                    'name' => (string) ($stationPayload['stationName'] ?? 'Stacja '.$externalId),
                    'city' => data_get($stationPayload, 'city.name'),
                    'street' => $stationPayload['addressStreet'] ?? null,
                    'latitude' => $stationPayload['gegrLat'] ?? null,
                    'longitude' => $stationPayload['gegrLon'] ?? null,
                    'is_active' => true,
                ],
            );

            $stationsCount++;
            $readingsCount += $this->syncIndexReading($station, $externalId);
        }

        Log::info('eco_uslugi.air_quality.sync.success', [
            'stations' => $stationsCount,
            'readings' => $readingsCount,
        ]);

        return ['stations' => $stationsCount, 'readings' => $readingsCount];
    }

    private function syncIndexReading(AirQualityStation $station, string $externalId): int
    {
        $index = $this->client->stationIndex($externalId);
        $measuredAt = $index['stCalcDate'] ?? null;
        $indexLevel = data_get($index, 'stIndexLevel');

        if (! is_array($indexLevel)) {
            return 0;
        }

        AirQualityReading::query()->updateOrCreate(
            [
                'eco_air_quality_station_id' => $station->id,
                'parameter_code' => 'AQI',
                'measured_at' => $measuredAt,
            ],
            [
                'parameter_name' => 'Indeks jakości powietrza',
                'value' => $indexLevel['id'] ?? null,
                'index_value' => $indexLevel['id'] ?? null,
                'index_category_name' => $indexLevel['indexLevelName'] ?? null,
                'fetched_at' => now(),
            ],
        );

        return 1;
    }
}

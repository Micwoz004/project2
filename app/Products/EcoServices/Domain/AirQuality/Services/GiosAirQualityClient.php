<?php

namespace App\Products\EcoServices\Domain\AirQuality\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GiosAirQualityClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function stations(): array
    {
        $baseUrl = rtrim((string) config('eco_services.gios.base_url'), '/');

        Log::info('eco_services.gios.stations.start');

        if ($baseUrl === '') {
            Log::warning('eco_services.gios.stations.rejected_missing_base_url');

            return [];
        }

        $payload = Http::timeout((int) config('eco_services.gios.timeout', 12))
            ->get($baseUrl.'/pjp-api/rest/station/findAll')
            ->throw()
            ->json();

        Log::info('eco_services.gios.stations.success', [
            'stations_count' => is_countable($payload) ? count($payload) : 0,
        ]);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function stationIndex(string|int $stationId): array
    {
        $baseUrl = rtrim((string) config('eco_services.gios.base_url'), '/');

        if ($baseUrl === '') {
            return [];
        }

        $payload = Http::timeout((int) config('eco_services.gios.timeout', 12))
            ->get($baseUrl.'/pjp-api/rest/aqindex/getIndex/'.$stationId)
            ->throw()
            ->json();

        return is_array($payload) ? $payload : [];
    }
}

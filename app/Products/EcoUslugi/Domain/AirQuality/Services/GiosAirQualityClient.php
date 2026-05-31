<?php

namespace App\Products\EcoUslugi\Domain\AirQuality\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GiosAirQualityClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function stations(): array
    {
        $baseUrl = rtrim((string) config('eco_uslugi.gios.base_url'), '/');

        Log::info('eco_uslugi.gios.stations.start');

        if ($baseUrl === '') {
            Log::warning('eco_uslugi.gios.stations.rejected_missing_base_url');

            return [];
        }

        $payload = Http::timeout((int) config('eco_uslugi.gios.timeout', 12))
            ->get($baseUrl.'/pjp-api/rest/station/findAll')
            ->throw()
            ->json();

        Log::info('eco_uslugi.gios.stations.success', [
            'stations_count' => is_countable($payload) ? count($payload) : 0,
        ]);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function stationIndex(string|int $stationId): array
    {
        $baseUrl = rtrim((string) config('eco_uslugi.gios.base_url'), '/');

        if ($baseUrl === '') {
            return [];
        }

        $payload = Http::timeout((int) config('eco_uslugi.gios.timeout', 12))
            ->get($baseUrl.'/pjp-api/rest/aqindex/getIndex/'.$stationId)
            ->throw()
            ->json();

        return is_array($payload) ? $payload : [];
    }
}

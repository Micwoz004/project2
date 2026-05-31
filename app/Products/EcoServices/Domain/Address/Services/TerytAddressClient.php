<?php

namespace App\Products\EcoServices\Domain\Address\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TerytAddressClient
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(array $payload): array
    {
        $baseUrl = config('eco_services.teryt.base_url');

        Log::info('eco_services.teryt.lookup.start');

        if (! is_string($baseUrl) || $baseUrl === '') {
            Log::warning('eco_services.teryt.lookup.rejected_missing_base_url');

            return [];
        }

        $response = Http::timeout((int) config('eco_services.teryt.timeout', 10))
            ->withToken((string) config('eco_services.teryt.token'))
            ->get(rtrim($baseUrl, '/').'/addresses', $payload)
            ->throw()
            ->json();

        Log::info('eco_services.teryt.lookup.success');

        return is_array($response) ? $response : [];
    }
}

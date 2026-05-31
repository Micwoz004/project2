<?php

namespace App\Products\EkoUslugi\Domain\Address\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TerytAddressClient
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(array $payload): array
    {
        $baseUrl = config('eko_uslugi.teryt.base_url');

        Log::info('eko_uslugi.teryt.lookup.start');

        if (! is_string($baseUrl) || $baseUrl === '') {
            Log::warning('eko_uslugi.teryt.lookup.rejected_missing_base_url');

            return [];
        }

        $response = Http::timeout((int) config('eko_uslugi.teryt.timeout', 10))
            ->withToken((string) config('eko_uslugi.teryt.token'))
            ->get(rtrim($baseUrl, '/').'/addresses', $payload)
            ->throw()
            ->json();

        Log::info('eko_uslugi.teryt.lookup.success');

        return is_array($response) ? $response : [];
    }
}

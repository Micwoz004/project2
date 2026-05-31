<?php

return [
    'gemini' => [
        'api_key' => env('EKO_USLUGI_GEMINI_API_KEY'),
        'model' => env('EKO_USLUGI_GEMINI_MODEL', 'gemini-1.5-flash'),
        'timeout' => env('EKO_USLUGI_GEMINI_TIMEOUT', 20),
    ],

    'gios' => [
        'base_url' => env('EKO_USLUGI_GIOS_BASE_URL', 'https://api.gios.gov.pl'),
        'timeout' => env('EKO_USLUGI_GIOS_TIMEOUT', 12),
        'cache_ttl' => env('EKO_USLUGI_GIOS_CACHE_TTL', 900),
    ],

    'teryt' => [
        'base_url' => env('EKO_USLUGI_TERYT_BASE_URL'),
        'token' => env('EKO_USLUGI_TERYT_TOKEN'),
        'timeout' => env('EKO_USLUGI_TERYT_TIMEOUT', 10),
    ],
];

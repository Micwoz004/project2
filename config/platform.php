<?php

use App\Platform\Clients\Models\Client;
use App\Platform\Products\Enums\ProductKey;

return [
    'default_client_slug' => env('PLATFORM_DEFAULT_CLIENT_SLUG', Client::DEFAULT_SLUG),

    'default_products' => [
        ProductKey::CivicBudget->value,
        ProductKey::EcoUslugi->value,
    ],
];

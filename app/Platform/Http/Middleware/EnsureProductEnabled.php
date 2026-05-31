<?php

namespace App\Platform\Http\Middleware;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Platform\Products\Enums\ProductKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductEnabled
{
    public function __construct(
        private readonly CurrentClient $currentClient,
    ) {}

    public function handle(Request $request, Closure $next, string $productKey): Response
    {
        $client = $this->currentClient->require();
        $product = ProductKey::tryFrom($productKey);

        if (! $product instanceof ProductKey || ! $client->isProductEnabled($product)) {
            Log::warning('platform.product_access.rejected', [
                'client_id' => $client instanceof Client ? $client->id : null,
                'product_key' => $productKey,
                'path' => $request->path(),
            ]);

            abort(404);
        }

        return $next($request);
    }
}

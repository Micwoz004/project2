<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Platform\Clients\Services\CurrentClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MobileProductController extends Controller
{
    public function index(CurrentClient $currentClient): JsonResponse
    {
        $client = $currentClient->require();

        Log::info('mobile_products.index.start', [
            'client_id' => $client->id,
        ]);

        $products = $client->products()
            ->enabled()
            ->get()
            ->map(fn ($product): array => [
                'key' => $product->product_key->value,
                'label' => $product->product_key->label(),
            ])
            ->values()
            ->all();

        Log::info('mobile_products.index.success', [
            'client_id' => $client->id,
            'products_count' => count($products),
        ]);

        return response()->json([
            'items' => $products,
        ]);
    }
}

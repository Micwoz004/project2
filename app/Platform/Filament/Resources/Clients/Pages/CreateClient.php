<?php

namespace App\Platform\Filament\Resources\Clients\Pages;

use App\Platform\Filament\Resources\Clients\ClientResource;
use App\Platform\Products\Enums\ProductKey;
use App\Platform\Products\Models\ClientProduct;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $client = parent::handleRecordCreation($data);

        foreach (ProductKey::cases() as $productKey) {
            ClientProduct::query()->firstOrCreate([
                'client_id' => $client->id,
                'product_key' => $productKey->value,
            ], [
                'enabled' => false,
                'settings' => [],
            ]);
        }

        return $client;
    }
}

<?php

namespace App\Platform\Products\Models;

use App\Platform\Clients\Models\Client;
use App\Platform\Products\Enums\ProductKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProduct extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'product_key' => ProductKey::class,
            'enabled' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}

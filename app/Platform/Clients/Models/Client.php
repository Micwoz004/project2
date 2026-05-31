<?php

namespace App\Platform\Clients\Models;

use App\Models\User;
use App\Platform\Products\Enums\ProductKey;
use App\Platform\Products\Models\ClientProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    public const DEFAULT_SLUG = 'default';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public static function default(): self
    {
        return self::query()->firstOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            [
                'name' => 'Domyślny klient',
                'is_active' => true,
                'settings' => [],
            ],
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(ClientProduct::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_memberships')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    public function isProductEnabled(ProductKey $productKey): bool
    {
        return $this->products()
            ->where('product_key', $productKey->value)
            ->where('enabled', true)
            ->exists();
    }
}

<?php

namespace App\Products\EcoServices\Domain\Waste\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EcoServices\Domain\Waste\Services\WasteNameNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WasteItem extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eco_waste_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'goes_to_pszok' => 'boolean',
        ];
    }

    public static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->normalized_name = app(WasteNameNormalizer::class)->normalize($item->name);
        });
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'eco_waste_fraction_id');
    }

    public function synonyms(): HasMany
    {
        return $this->hasMany(WasteItemSynonym::class, 'eco_waste_item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

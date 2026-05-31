<?php

namespace App\Products\EkoUslugi\Domain\Waste\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EkoUslugi\Domain\Waste\Services\WasteNameNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WasteItem extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eko_waste_items';

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
        return $this->belongsTo(WasteFraction::class, 'eko_waste_fraction_id');
    }

    public function synonyms(): HasMany
    {
        return $this->hasMany(WasteItemSynonym::class, 'eko_waste_item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

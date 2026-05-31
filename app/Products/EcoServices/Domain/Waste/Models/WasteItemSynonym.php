<?php

namespace App\Products\EcoServices\Domain\Waste\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EcoServices\Domain\Waste\Services\WasteNameNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteItemSynonym extends Model
{
    use BelongsToClient;

    protected $table = 'eco_waste_item_synonyms';

    protected $guarded = [];

    public static function booted(): void
    {
        static::saving(function (self $synonym): void {
            $synonym->normalized_synonym = app(WasteNameNormalizer::class)->normalize($synonym->synonym);
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'eco_waste_item_id');
    }
}

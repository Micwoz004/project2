<?php

namespace App\Products\EkoUslugi\Domain\Waste\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EkoUslugi\Domain\Waste\Services\WasteNameNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteItemSynonym extends Model
{
    use BelongsToClient;

    protected $table = 'eko_waste_item_synonyms';

    protected $guarded = [];

    public static function booted(): void
    {
        static::saving(function (self $synonym): void {
            $synonym->normalized_synonym = app(WasteNameNormalizer::class)->normalize($synonym->synonym);
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'eko_waste_item_id');
    }
}

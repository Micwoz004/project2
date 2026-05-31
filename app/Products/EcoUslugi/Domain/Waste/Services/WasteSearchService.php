<?php

namespace App\Products\EcoUslugi\Domain\Waste\Services;

use App\Products\EcoUslugi\Domain\Waste\Models\WasteItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class WasteSearchService
{
    public function __construct(
        private readonly WasteNameNormalizer $normalizer,
    ) {}

    /**
     * @return Collection<int, WasteItem>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $normalized = $this->normalizer->normalize($query);

        Log::info('eco_uslugi.waste.search.start', [
            'query_length' => mb_strlen($query),
        ]);

        if ($normalized === '') {
            Log::warning('eco_uslugi.waste.search.rejected_empty');

            return WasteItem::newCollection();
        }

        $items = WasteItem::query()
            ->with(['fraction', 'synonyms'])
            ->active()
            ->where(function ($query) use ($normalized): void {
                $query
                    ->where('normalized_name', 'like', "%{$normalized}%")
                    ->orWhereHas('synonyms', fn ($query) => $query->where('normalized_synonym', 'like', "%{$normalized}%"));
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        Log::info('eco_uslugi.waste.search.success', [
            'results_count' => $items->count(),
        ]);

        return $items;
    }
}

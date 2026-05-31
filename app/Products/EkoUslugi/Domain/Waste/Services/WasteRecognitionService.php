<?php

namespace App\Products\EkoUslugi\Domain\Waste\Services;

use App\Products\EkoUslugi\Domain\Waste\Models\WasteItem;
use Illuminate\Http\UploadedFile;

class WasteRecognitionService
{
    public function __construct(
        private readonly WasteRecognitionClient $client,
        private readonly WasteSearchService $searchService,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array<string, mixed>>
     */
    public function recognize(array $files): array
    {
        return collect($this->client->recognize($files))
            ->map(function (array $result): array {
                $matched = collect($result['synonyms'])
                    ->map(fn (string $query) => $this->searchService->search($query, 1)->first())
                    ->first(fn (?WasteItem $item): bool => $item instanceof WasteItem);

                return [
                    'fileName' => $result['fileName'],
                    'interpretation' => [
                        'objectSummary' => $result['objectSummary'],
                        'objectSynonyms' => $result['synonyms'],
                    ],
                    'matchedWasteItem' => $matched instanceof WasteItem ? [
                        'id' => $matched->id,
                        'name' => $matched->name,
                        'instruction' => $matched->instruction,
                        'fraction' => $matched->fraction ? [
                            'id' => $matched->fraction->id,
                            'name' => $matched->fraction->name,
                            'color' => $matched->fraction->color,
                        ] : null,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }
}

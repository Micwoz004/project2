<?php

namespace App\Domain\Settings\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Settings\Models\ContentPage;
use Illuminate\Support\Facades\Log;

class ContentPageResolver
{
    public function __construct(
        private readonly ApplicationSettings $settings,
    ) {}

    public function bodyFor(?BudgetEdition $edition, string $symbol): ?string
    {
        Log::info('content_page.resolve.start', [
            'budget_edition_id' => $edition?->id,
            'symbol' => $symbol,
        ]);

        if ($symbol === ContentPage::SYMBOL_VOID) {
            $body = $this->settings->string('owner', 'pageProcessAbsence', '');

            Log::info('content_page.resolve.success', [
                'budget_edition_id' => $edition?->id,
                'symbol' => $symbol,
                'source' => 'application_settings',
            ]);

            return $body;
        }

        if (! $edition instanceof BudgetEdition) {
            Log::warning('content_page.resolve.rejected_missing_edition', [
                'symbol' => $symbol,
            ]);

            return null;
        }

        $body = ContentPage::query()
            ->where('budget_edition_id', $edition->id)
            ->where('symbol', $symbol)
            ->value('body');

        if ($body === null) {
            Log::warning('content_page.resolve.not_found', [
                'budget_edition_id' => $edition->id,
                'symbol' => $symbol,
            ]);

            return null;
        }

        Log::info('content_page.resolve.success', [
            'budget_edition_id' => $edition->id,
            'symbol' => $symbol,
            'source' => 'content_pages',
        ]);

        return $body;
    }
}

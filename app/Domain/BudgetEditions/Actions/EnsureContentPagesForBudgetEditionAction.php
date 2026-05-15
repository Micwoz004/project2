<?php

namespace App\Domain\BudgetEditions\Actions;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Settings\Models\ContentPage;
use Illuminate\Support\Facades\Log;

class EnsureContentPagesForBudgetEditionAction
{
    public function execute(BudgetEdition $edition): void
    {
        Log::info('budget_edition.content_pages.ensure.start', [
            'budget_edition_id' => $edition->id,
        ]);

        foreach (ContentPage::LEGACY_SYMBOLS as $symbol) {
            ContentPage::query()->firstOrCreate([
                'budget_edition_id' => $edition->id,
                'symbol' => $symbol,
            ], [
                'body' => '',
            ]);
        }

        Log::info('budget_edition.content_pages.ensure.success', [
            'budget_edition_id' => $edition->id,
            'symbols_count' => count(ContentPage::LEGACY_SYMBOLS),
        ]);
    }
}

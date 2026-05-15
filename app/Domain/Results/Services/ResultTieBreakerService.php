<?php

namespace App\Domain\Results\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ResultTieBreakerService
{
    public function __construct(
        private readonly ResultsCalculator $resultsCalculator,
    ) {}

    public function tiedProjectGroups(BudgetEdition $edition): Collection
    {
        Log::info('results.tie_breaker.detect.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $groups = $this->resultsCalculator
            ->projectTotals($edition)
            ->groupBy(fn ($row) => (int) $row->points)
            ->filter(fn (Collection $rows, int $points) => $points > 0 && $rows->count() > 1)
            ->map(fn (Collection $rows, int $points) => [
                'points' => $points,
                'requires_manual_decision' => true,
                'project_ids' => $rows->pluck('project_id')->values()->all(),
                'ranking_order' => $rows->map(fn ($row) => [
                    'project_id' => $row->project_id,
                    'number_drawn' => $row->number_drawn,
                ])->values()->all(),
            ])
            ->values();

        Log::info('results.tie_breaker.detect.success', [
            'budget_edition_id' => $edition->id,
            'groups_count' => $groups->count(),
        ]);

        return $groups;
    }
}

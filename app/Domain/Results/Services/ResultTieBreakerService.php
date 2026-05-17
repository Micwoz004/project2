<?php

namespace App\Domain\Results\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Results\Models\ResultTieDecision;
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

        $decisions = ResultTieDecision::query()
            ->where('budget_edition_id', $edition->id)
            ->get()
            ->keyBy('group_key');

        $groups = $this->resultsCalculator
            ->projectTotals($edition)
            ->groupBy(fn ($row) => (int) $row->points)
            ->filter(fn (Collection $rows, int $points) => $points > 0 && $rows->count() > 1)
            ->map(fn (Collection $rows, int $points) => $this->tieGroup($rows, $points, $decisions))
            ->values();

        Log::info('results.tie_breaker.detect.success', [
            'budget_edition_id' => $edition->id,
            'groups_count' => $groups->count(),
        ]);

        return $groups;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<string, ResultTieDecision>  $decisions
     * @return array<string, mixed>
     */
    private function tieGroup(Collection $rows, int $points, Collection $decisions): array
    {
        $projectIds = $rows
            ->pluck('project_id')
            ->map(fn (int $projectId): int => $projectId)
            ->values()
            ->all();
        $decision = $decisions->get(ResultTieDecision::groupKey($points, $projectIds));

        return [
            'points' => $points,
            'requires_manual_decision' => $decision === null,
            'decision' => $decision === null ? null : [
                'winner_project_id' => $decision->winner_project_id,
                'decided_by_id' => $decision->decided_by_id,
                'decided_at' => $decision->decided_at?->toDateTimeString(),
                'notes' => $decision->notes,
            ],
            'project_ids' => $projectIds,
            'ranking_order' => $rows->map(fn ($row) => [
                'project_id' => $row->project_id,
                'number_drawn' => $row->number_drawn,
            ])->values()->all(),
        ];
    }
}

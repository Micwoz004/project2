<?php

namespace App\Domain\Results\Actions;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Domain\Results\Models\ResultTieDecision;
use App\Domain\Results\Services\ResultTieBreakerService;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class ResolveResultTieDecisionAction
{
    public function __construct(
        private readonly ResultTieBreakerService $tieBreakerService,
    ) {}

    /**
     * @param  list<int>  $projectIds
     */
    public function execute(
        BudgetEdition $edition,
        array $projectIds,
        Project $winner,
        User $operator,
        ?string $notes = null,
    ): ResultTieDecision {
        Log::info('results.tie_decision.resolve.start', [
            'budget_edition_id' => $edition->id,
            'winner_project_id' => $winner->id,
            'operator_id' => $operator->id,
            'projects_count' => count($projectIds),
        ]);

        if (! $this->canResolveTie($operator)) {
            Log::warning('results.tie_decision.resolve.rejected_authorization', [
                'budget_edition_id' => $edition->id,
                'operator_id' => $operator->id,
            ]);

            throw new DomainException('Brak uprawnień do rozstrzygnięcia remisu wyników.');
        }

        $normalizedProjectIds = $this->normalizeProjectIds($projectIds);
        $tieGroup = $this->findTieGroup($edition, $normalizedProjectIds);

        if ($tieGroup === null) {
            Log::warning('results.tie_decision.resolve.rejected_missing_tie', [
                'budget_edition_id' => $edition->id,
                'project_ids' => $normalizedProjectIds,
            ]);

            throw new DomainException('Nie znaleziono aktywnego remisu dla wskazanych projektów.');
        }

        if (! in_array($winner->id, $normalizedProjectIds, true)) {
            Log::warning('results.tie_decision.resolve.rejected_winner_outside_group', [
                'budget_edition_id' => $edition->id,
                'winner_project_id' => $winner->id,
            ]);

            throw new DomainException('Wybrany projekt nie należy do wskazanej grupy remisowej.');
        }

        $points = (int) $tieGroup['points'];
        $decision = ResultTieDecision::query()->updateOrCreate([
            'budget_edition_id' => $edition->id,
            'group_key' => ResultTieDecision::groupKey($points, $normalizedProjectIds),
        ], [
            'points' => $points,
            'project_ids' => $normalizedProjectIds,
            'winner_project_id' => $winner->id,
            'decided_by_id' => $operator->id,
            'decided_at' => now(),
            'notes' => $notes,
        ]);

        Log::info('results.tie_decision.resolve.success', [
            'budget_edition_id' => $edition->id,
            'decision_id' => $decision->id,
            'winner_project_id' => $winner->id,
        ]);

        return $decision->refresh();
    }

    private function canResolveTie(User $operator): bool
    {
        return $operator->can('reports.export') || $operator->hasAnyRole(['admin', 'bdo']);
    }

    /**
     * @param  list<int>  $projectIds
     * @return list<int>
     */
    private function normalizeProjectIds(array $projectIds): array
    {
        return collect($projectIds)
            ->map(fn (int $projectId): int => $projectId)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $projectIds
     * @return array<string, mixed>|null
     */
    private function findTieGroup(BudgetEdition $edition, array $projectIds): ?array
    {
        return $this->tieBreakerService
            ->tiedProjectGroups($edition)
            ->first(function (array $group) use ($projectIds): bool {
                $groupProjectIds = $this->normalizeProjectIds($group['project_ids']);

                return $groupProjectIds === $projectIds;
            });
    }
}

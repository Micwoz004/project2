<?php

namespace App\Domain\Results\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Domain\Reports\Services\VoteCardReportService;
use App\Domain\Results\Models\ResultPublication;
use App\Domain\Results\Models\ResultTieDecision;
use App\Domain\Voting\Enums\VoteCardStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ResultsDashboardService
{
    public function __construct(
        private readonly ResultsCalculator $resultsCalculator,
        private readonly ResultsPublicationService $publicationService,
        private readonly ResultTieBreakerService $tieBreakerService,
        private readonly VoteCardReportService $voteCardReportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(BudgetEdition $edition): array
    {
        Log::info('results_dashboard.summary.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $projectTotals = $this->resultsCalculator->projectTotals($edition);
        $projects = Project::query()
            ->with('area')
            ->whereIn('id', $projectTotals->pluck('project_id'))
            ->get()
            ->keyBy('id');

        $statusCounts = $this->voteCardReportService->statusCounts($edition);
        $tieGroups = $this->tieBreakerService->tiedProjectGroups($edition);
        $categoryDifferences = $this->resultsCalculator
            ->categoryComparisonTotals($edition)
            ->filter(fn (object $row): bool => (int) $row->difference !== 0)
            ->values();
        $projectRows = $this->projectTotals($projectTotals, $projects);

        $summary = [
            'published' => $this->publicationService->canPublishPublicResults($edition),
            'total_points' => (int) $projectTotals->sum('points'),
            'projects_count' => $projectTotals->count(),
            'status_counts' => $this->statusCounts($statusCounts),
            'project_totals' => $projectRows,
            'top_projects' => array_slice($projectRows, 0, 10),
            'area_totals' => $this->areaTotals($edition),
            'category_totals' => $this->categoryTotals($edition),
            'tie_groups' => $this->tieGroups($tieGroups, $projects),
            'category_differences' => $this->categoryDifferences($categoryDifferences),
            'latest_publication' => $this->latestPublication($edition),
        ];

        Log::info('results_dashboard.summary.success', [
            'budget_edition_id' => $edition->id,
            'projects_count' => $summary['projects_count'],
            'tie_groups_count' => count($summary['tie_groups']),
        ]);

        return $summary;
    }

    /**
     * @param  Collection<int, int>  $statusCounts
     * @return array<string, int>
     */
    private function statusCounts(Collection $statusCounts): array
    {
        return [
            VoteCardStatus::Accepted->label() => (int) ($statusCounts->get(VoteCardStatus::Accepted->value) ?? 0),
            VoteCardStatus::Rejected->label() => (int) ($statusCounts->get(VoteCardStatus::Rejected->value) ?? 0),
            VoteCardStatus::Verifying->label() => (int) ($statusCounts->get(VoteCardStatus::Verifying->value) ?? 0),
        ];
    }

    /**
     * @param  Collection<int, object>  $projectTotals
     * @param  Collection<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    private function projectTotals(Collection $projectTotals, Collection $projects): array
    {
        return $projectTotals
            ->map(function (object $row) use ($projects): array {
                $project = $projects->get($row->project_id);

                return [
                    'project_id' => (int) $row->project_id,
                    'number_drawn' => $row->number_drawn,
                    'title' => $project?->title ?? 'Projekt '.$row->project_id,
                    'area' => $project?->area?->name ?? '-',
                    'points' => (int) $row->points,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestPublication(BudgetEdition $edition): ?array
    {
        $publication = ResultPublication::query()
            ->where('budget_edition_id', $edition->id)
            ->latest('version')
            ->first();

        if (! $publication instanceof ResultPublication) {
            return null;
        }

        return [
            'id' => $publication->id,
            'version' => $publication->version,
            'published_at' => $publication->published_at?->toDateTimeString(),
            'published_by_id' => $publication->published_by_id,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function areaTotals(BudgetEdition $edition): array
    {
        return $this->resultsCalculator
            ->areaTotals($edition)
            ->map(fn (object $row): array => [
                'name' => $row->name ?? 'Bez obszaru',
                'symbol' => $row->symbol,
                'is_local' => (bool) $row->is_local,
                'points' => (int) $row->points,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categoryTotals(BudgetEdition $edition): array
    {
        return $this->resultsCalculator
            ->categoryTotals($edition)
            ->map(fn (object $row): array => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'points' => (int) $row->points,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $tieGroups
     * @param  Collection<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    private function tieGroups(Collection $tieGroups, Collection $projects): array
    {
        return $tieGroups
            ->map(function (array $group) use ($projects): array {
                $groupKey = ResultTieDecision::groupKey((int) $group['points'], $group['project_ids']);

                return [
                    'group_key' => $groupKey,
                    'form_key' => sha1($groupKey),
                    'points' => $group['points'],
                    'project_ids' => $group['project_ids'],
                    'requires_manual_decision' => $group['requires_manual_decision'],
                    'decision' => $group['decision'],
                    'projects' => collect($group['ranking_order'])
                        ->map(function (array $row) use ($projects): array {
                            $project = $projects->get($row['project_id']);

                            return [
                                'project_id' => (int) $row['project_id'],
                                'number_drawn' => $row['number_drawn'],
                                'title' => $project?->title ?? 'Projekt '.$row['project_id'],
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $categoryDifferences
     * @return list<array<string, mixed>>
     */
    private function categoryDifferences(Collection $categoryDifferences): array
    {
        return $categoryDifferences
            ->map(fn (object $row): array => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'primary_points' => (int) $row->primary_points,
                'multi_category_points' => (int) $row->multi_category_points,
                'difference' => (int) $row->difference,
            ])
            ->values()
            ->all();
    }
}

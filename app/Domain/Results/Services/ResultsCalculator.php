<?php

namespace App\Domain\Results\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultsCalculator
{
    public function projectTotals(BudgetEdition $edition): Collection
    {
        Log::info('results.calculate.start', [
            'budget_edition_id' => $edition->id,
            'scope' => 'projects',
        ]);

        $totals = Vote::query()
            ->join('projects', 'votes.project_id', '=', 'projects.id')
            ->select([
                'votes.project_id',
                'projects.number_drawn',
                DB::raw('SUM(votes.points) as points'),
            ])
            ->whereHas('voteCard', fn (Builder $query) => $this->acceptedCardsForEdition($query, $edition))
            ->groupBy('votes.project_id', 'projects.number_drawn')
            ->orderByDesc('points')
            ->orderBy('projects.number_drawn')
            ->orderBy('votes.project_id')
            ->get();

        Log::info('results.calculate.success', [
            'budget_edition_id' => $edition->id,
            'scope' => 'projects',
            'projects_count' => $totals->count(),
        ]);

        return $totals;
    }

    public function areaTotals(BudgetEdition $edition): Collection
    {
        Log::info('results.calculate.start', [
            'budget_edition_id' => $edition->id,
            'scope' => 'areas',
        ]);

        $totals = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('projects', 'votes.project_id', '=', 'projects.id')
            ->leftJoin('project_areas', 'projects.project_area_id', '=', 'project_areas.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'project_areas.id as project_area_id',
                'project_areas.name',
                'project_areas.symbol',
                'project_areas.is_local',
                DB::raw('SUM(votes.points) as points'),
            ])
            ->groupBy('project_areas.id', 'project_areas.name', 'project_areas.symbol', 'project_areas.is_local')
            ->orderBy('project_areas.is_local')
            ->orderBy('project_areas.name')
            ->get();

        Log::info('results.calculate.success', [
            'budget_edition_id' => $edition->id,
            'scope' => 'areas',
            'areas_count' => $totals->count(),
        ]);

        return $totals;
    }

    public function categoryTotals(BudgetEdition $edition): Collection
    {
        Log::info('results.calculate.start', [
            'budget_edition_id' => $edition->id,
            'scope' => 'categories',
        ]);

        $pivotTotals = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('projects', 'votes.project_id', '=', 'projects.id')
            ->join('category_project', 'projects.id', '=', 'category_project.project_id')
            ->join('categories', 'category_project.category_id', '=', 'categories.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'categories.id as category_id',
                'categories.name',
                DB::raw('SUM(votes.points) as points'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $fallbackTotals = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('projects', 'votes.project_id', '=', 'projects.id')
            ->join('categories', 'projects.category_id', '=', 'categories.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('category_project')
                    ->whereColumn('category_project.project_id', 'projects.id');
            })
            ->select([
                'categories.id as category_id',
                'categories.name',
                DB::raw('SUM(votes.points) as points'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $totals = $pivotTotals
            ->concat($fallbackTotals)
            ->sortBy('name')
            ->values();

        Log::info('results.calculate.success', [
            'budget_edition_id' => $edition->id,
            'scope' => 'categories',
            'categories_count' => $totals->count(),
        ]);

        return $totals;
    }

    public function categoryComparisonTotals(BudgetEdition $edition): Collection
    {
        Log::info('results.calculate.start', [
            'budget_edition_id' => $edition->id,
            'scope' => 'category_comparison',
        ]);

        $primaryTotals = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('projects', 'votes.project_id', '=', 'projects.id')
            ->join('categories', 'projects.category_id', '=', 'categories.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'categories.id as category_id',
                'categories.name',
                DB::raw('SUM(votes.points) as points'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->keyBy('category_id');

        $multiCategoryTotals = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('projects', 'votes.project_id', '=', 'projects.id')
            ->join('category_project', 'projects.id', '=', 'category_project.project_id')
            ->join('categories', 'category_project.category_id', '=', 'categories.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'categories.id as category_id',
                'categories.name',
                DB::raw('SUM(votes.points) as points'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->keyBy('category_id');

        $totals = $primaryTotals
            ->keys()
            ->merge($multiCategoryTotals->keys())
            ->unique()
            ->map(function (int $categoryId) use ($primaryTotals, $multiCategoryTotals): object {
                $primary = $primaryTotals->get($categoryId);
                $multiCategory = $multiCategoryTotals->get($categoryId);
                $primaryPoints = (int) ($primary?->points ?? 0);
                $multiCategoryPoints = (int) ($multiCategory?->points ?? 0);

                return (object) [
                    'category_id' => $categoryId,
                    'name' => $primary?->name ?? $multiCategory->name,
                    'primary_points' => $primaryPoints,
                    'multi_category_points' => $multiCategoryPoints,
                    'difference' => $multiCategoryPoints - $primaryPoints,
                ];
            })
            ->sortBy('name')
            ->values();

        Log::info('results.calculate.success', [
            'budget_edition_id' => $edition->id,
            'scope' => 'category_comparison',
            'categories_count' => $totals->count(),
        ]);

        return $totals;
    }

    private function acceptedCardsForEdition(Builder $query, BudgetEdition $edition): void
    {
        $query
            ->where('budget_edition_id', $edition->id)
            ->where('status', VoteCardStatus::Accepted->value);
    }
}
